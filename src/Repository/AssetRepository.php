<?php

namespace App\Repository;

use App\Entity\Asset;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Asset>
 */
class AssetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Asset::class);
    }

    /**
     * Find an asset by its unique asset ID.
     */
    public function findOneByAssetId(string $assetId): ?Asset
    {
        return $this->findOneBy([
            'assetId' => $assetId,
        ]);
    }

    /**
     * Return one page of assets.
     *
     * @return Asset[]
     */
    public function findPaginated(int $page, int $limit = 10): array
    {
        $page = max(1, $page);
        $limit = max(1, $limit);

        return $this->createQueryBuilder('a')
            ->orderBy('a.id', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find an asset by its PC name.
     */
    public function findOneByPcName(string $pcName): ?Asset
    {
        return $this->findOneBy([
            'pcName' => $pcName,
        ]);
    }

    /**
     * Find an asset by its serial number.
     */
    public function findOneBySerialNumber(string $serialNumber): ?Asset
    {
        return $this->findOneBy([
            'serialNumber' => $serialNumber,
        ]);
    }

    /**
     * Search assets by text across common fields.
     *
     * @return Asset[]
     */
    public function search(string $term): array
    {
        $term = trim($term);

        if ($term === '') {
            return $this->findBy([], ['id' => 'DESC']);
        }

        return $this->createQueryBuilder('a')
            ->where('LOWER(a.assetId) LIKE LOWER(:term)')
            ->orWhere('LOWER(a.userName) LIKE LOWER(:term)')
            ->orWhere('LOWER(a.pcName) LIKE LOWER(:term)')
            ->orWhere('LOWER(a.serialNumber) LIKE LOWER(:term)')
            ->orWhere('LOWER(a.model) LIKE LOWER(:term)')
            ->orWhere('LOWER(a.department) LIKE LOWER(:term)')
            ->orWhere('LOWER(a.officeLocation) LIKE LOWER(:term)')
            ->setParameter('term', '%' . $term . '%')
            ->orderBy('a.pcName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find assets by status.
     *
     * @return Asset[]
     */
    public function findByStatus(string $status): array
    {
        return $this->findBy(
            ['status' => $status],
            ['pcName' => 'ASC']
        );
    }

    /**
     * Find assets belonging to a department.
     *
     * @return Asset[]
     */
    public function findByDepartment(string $department): array
    {
        return $this->createQueryBuilder('a')
            ->where('LOWER(a.department) = LOWER(:department)')
            ->setParameter('department', trim($department))
            ->orderBy('a.pcName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find assets whose warranty expires before a given date.
     *
     * @return Asset[]
     */
    public function findWarrantiesExpiringBefore(
        \DateTimeImmutable $date
    ): array {
        return $this->createQueryBuilder('a')
            ->where('a.warrantyExpiry IS NOT NULL')
            ->andWhere('a.warrantyExpiry <= :date')
            ->andWhere('a.status != :retired')
            ->setParameter('date', $date)
            ->setParameter('retired', 'Retired')
            ->orderBy('a.warrantyExpiry', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find assets with warranties expiring within the next number of days.
     *
     * @return Asset[]
     */
    public function findWarrantiesExpiringWithin(int $days = 30): array
    {
        if ($days < 0) {
            throw new \InvalidArgumentException(
                'The number of days cannot be negative.'
            );
        }

        $today = new \DateTimeImmutable('today');
        $limit = $today->modify(sprintf('+%d days', $days));

        return $this->createQueryBuilder('a')
            ->where('a.warrantyExpiry >= :today')
            ->andWhere('a.warrantyExpiry <= :limit')
            ->andWhere('a.status != :retired')
            ->setParameter('today', $today)
            ->setParameter('limit', $limit)
            ->setParameter('retired', 'Retired')
            ->orderBy('a.warrantyExpiry', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count assets by status.
     */
    public function countByStatus(string $status): int
    {
        return $this->count([
            'status' => $status,
        ]);
    }

    /**
     * Count all assets.
     */
    public function countAll(): int
    {
        return $this->count([]);
    }
}
