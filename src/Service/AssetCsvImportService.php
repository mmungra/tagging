<?php

namespace App\Service;

use App\Entity\Asset;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Throwable;
class AssetCsvImportService
{
    private const REQUIRED_HEADERS = [
        'asset_id',
        'user_name',
        'pc_name',
        'serial_number',
        'model',
        'department',
        'office_location',
        'status',
    ];

    private const MAX_LENGTHS = [
        'asset_id' => 50,
        'user_name' => 150,
        'pc_name' => 100,
        'serial_number' => 150,
        'model' => 150,
        'department' => 100,
        'office_location' => 150,
        'status' => 20,
    ];

    private const ALLOWED_STATUSES = [
        'Active',
        'Repair',
        'Retired',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $projectDir,
    ) {
    }

    /**
     * Import a CSV file from var/import.
     *
     * @return array{
     *     imported: int,
     *     errors: list<string>
     * }
     */
    public function import(string $filename): array
    {
        // Only allow a filename, not an arbitrary filesystem path.
        if (
            $filename === ''
            || basename($filename) !== $filename
            || !str_ends_with(strtolower($filename), '.csv')
        ) {
            throw new RuntimeException(
                'Provide a CSV filename located in var/import.'
            );
        }

        $path = $this->projectDir . '/var/import/' . $filename;

        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException(
                sprintf('CSV file not found or not readable: %s', $filename)
            );
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Unable to open the CSV file.');
        }

        try {
            return $this->processFile($handle);
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param resource $handle
     *
     * @return array{imported: int, errors: list<string>}
     */
    private function processFile($handle): array
    {
        $headers = fgetcsv($handle, null, ';', '"', '');

        if ($headers === false || $headers === [null]) {
            return [
                'imported' => 0,
                'errors' => ['The CSV file is empty or has no header row.'],
            ];
        }

        // Remove a UTF-8 BOM if the CSV was exported by Excel.
        if (isset($headers[0])) {
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        }

        $headers = array_map(
            static fn ($header) => strtolower(trim((string) $header)),
            $headers
        );

        if (count($headers) !== count(array_unique($headers))) {
            return [
                'imported' => 0,
                'errors' => ['The CSV contains duplicate column headers.'],
            ];
        }

        $missingHeaders = array_diff(self::REQUIRED_HEADERS, $headers);

        if ($missingHeaders !== []) {
            return [
                'imported' => 0,
                'errors' => [
                    'Missing required CSV columns: '
                    . implode(', ', $missingHeaders),
                ],
            ];
        }

        $columnIndexes = array_flip($headers);
        $errors = [];
        $records = [];

        // Track duplicates within the CSV.
        $seen = [
            'asset_id' => [],
            'pc_name' => [],
            'serial_number' => [],
        ];

        $lineNumber = 1;

        while (($row = fgetcsv($handle, null, ';', '"', '')) !== false) {
            ++$lineNumber;

            // Ignore completely blank lines.
            if (
                count($row) === 1
                && trim((string) $row[0]) === ''
            ) {
                continue;
            }

            if (count($row) !== count($headers)) {
                $errors[] = sprintf(
                    'Line %d: expected %d columns, received %d.',
                    $lineNumber,
                    count($headers),
                    count($row)
                );
                continue;
            }

            $data = [];

            foreach ($headers as $index => $header) {
                $data[$header] = trim((string) $row[$index]);
            }

            $rowErrors = [];

            foreach (self::REQUIRED_HEADERS as $field) {
                if (($data[$field] ?? '') === '') {
                    $rowErrors[] = sprintf(
                        'Line %d: "%s" is required.',
                        $lineNumber,
                        $field
                    );
                }
            }

            foreach (self::MAX_LENGTHS as $field => $maxLength) {
                $value = $data[$field] ?? '';

                if (mb_strlen($value) > $maxLength) {
                    $rowErrors[] = sprintf(
                        'Line %d: "%s" exceeds %d characters.',
                        $lineNumber,
                        $field,
                        $maxLength
                    );
                }
            }

            $status = strtolower($data['status'] ?? '');
            $statusMap = [
                'active' => 'Active',
                'repair' => 'Repair',
                'retired' => 'Retired',
            ];

            if (!isset($statusMap[$status])) {
                $rowErrors[] = sprintf(
                    'Line %d: invalid status "%s". Allowed values: %s.',
                    $lineNumber,
                    $data['status'] ?? '',
                    implode(', ', self::ALLOWED_STATUSES)
                );
            } else {
                $data['status'] = $statusMap[$status];
            }

            $purchaseDate = $this->parseDate(
                $data['purchase_date'] ?? '',
                'purchase_date',
                $lineNumber,
                $rowErrors
            );

            $warrantyExpiry = $this->parseDate(
                $data['warranty_expiry'] ?? '',
                'warranty_expiry',
                $lineNumber,
                $rowErrors
            );

            if (
                $purchaseDate !== null
                && $warrantyExpiry !== null
                && $warrantyExpiry < $purchaseDate
            ) {
                $rowErrors[] = sprintf(
                    'Line %d: warranty_expiry cannot be before purchase_date.',
                    $lineNumber
                );
            }

            foreach (['asset_id', 'pc_name', 'serial_number'] as $field) {
                $value = strtolower($data[$field] ?? '');

                if ($value === '') {
                    continue;
                }

                if (isset($seen[$field][$value])) {
                    $rowErrors[] = sprintf(
                        'Line %d: duplicate "%s" value "%s" also appears on line %d.',
                        $lineNumber,
                        $field,
                        $data[$field],
                        $seen[$field][$value]
                    );
                } else {
                    $seen[$field][$value] = $lineNumber;
                }
            }

            if ($rowErrors !== []) {
                array_push($errors, ...$rowErrors);
                continue;
            }

            $records[] = [
                'line' => $lineNumber,
                'data' => $data,
                'purchaseDate' => $purchaseDate,
                'warrantyExpiry' => $warrantyExpiry,
            ];
        }

        if ($records === [] && $errors === []) {
            $errors[] = 'The CSV contains no inventory records.';
        }

        // Validate against existing database records before inserting.
        $existingAssets = $this->entityManager
            ->getRepository(Asset::class)
            ->findAll();

        $existing = [
            'asset_id' => [],
            'pc_name' => [],
            'serial_number' => [],
        ];

        foreach ($existingAssets as $asset) {
            $existing['asset_id'][strtolower($asset->getAssetId())] = true;
            $existing['pc_name'][strtolower($asset->getPcName())] = true;
            $existing['serial_number'][strtolower($asset->getSerialNumber())] = true;
        }

        foreach ($records as $record) {
            foreach (['asset_id', 'pc_name', 'serial_number'] as $field) {
                $value = strtolower($record['data'][$field]);

                if (isset($existing[$field][$value])) {
                    $errors[] = sprintf(
                        'Line %d: "%s" value "%s" already exists in the database.',
                        $record['line'],
                        $field,
                        $record['data'][$field]
                    );
                }
            }
        }

        // All-or-nothing: do not write anything if validation failed.
        if ($errors !== []) {
            return [
                'imported' => 0,
                'errors' => $errors,
            ];
        }

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            foreach ($records as $record) {
                $data = $record['data'];

                $asset = new Asset();
                $asset->setAssetId($data['asset_id']);
                $asset->setUserName($data['user_name']);
                $asset->setPcName($data['pc_name']);
                $asset->setSerialNumber($data['serial_number']);
                $asset->setModel($data['model']);
                $asset->setDepartment($data['department']);
                $asset->setOfficeLocation($data['office_location']);
                $asset->setStatus($data['status']);
                $asset->setPurchaseDate($record['purchaseDate']);
                $asset->setWarrantyExpiry($record['warrantyExpiry']);

                $this->entityManager->persist($asset);
            }

            $this->entityManager->flush();
            $connection->commit();

            return [
                'imported' => count($records),
                'errors' => [],
            ];
        } catch (Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            // Clear pending entities after a failed flush.
            $this->entityManager->clear();

            throw new RuntimeException(
                'CSV import failed during database insertion. '
                . 'No records were committed.',
                0,
                $exception
            );
        }
    }

    /**
     * Parse optional dates in strict YYYY-MM-DD format.
     *
     * @param list<string> $errors
     */
    private function parseDate(
        string $value,
        string $field,
        int $lineNumber,
        array &$errors
    ): ?DateTimeImmutable {
        if ($value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $dateErrors = DateTimeImmutable::getLastErrors();

        if (
            $date === false
            || (
                $dateErrors !== false
                && (
                    $dateErrors['warning_count'] > 0
                    || $dateErrors['error_count'] > 0
                )
            )
            || $date->format('Y-m-d') !== $value
        ) {
            $errors[] = sprintf(
                'Line %d: "%s" must be a valid date in YYYY-MM-DD format.',
                $lineNumber,
                $field
            );

            return null;
        }

        return $date;
    }
}
