<?php

namespace App\Entity;

use App\Repository\AssetRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: AssetRepository::class)]
#[ORM\Table(name: 'asset')]
#[ORM\UniqueConstraint(name: 'uniq_asset_asset_id', columns: ['asset_id'])]
#[ORM\UniqueConstraint(name: 'uniq_asset_serial_number', columns: ['serial_number'])]
#[UniqueEntity(
    fields: ['assetId'],
    message: 'This Asset ID already exists.'
)]
#[UniqueEntity(
    fields: ['serialNumber'],
    message: 'This serial number is already registered.'
)]
class Asset
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'asset_id', length: 50, unique: true)]
    private ?string $assetId = null;

    #[ORM\Column(name: 'user_name', length: 150)]
    private ?string $userName = null;

    #[ORM\Column(name: 'pc_name', length: 100, unique: false)]
    private ?string $pcName = null;

    #[ORM\Column(name: 'notes', length: 250, unique: false, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(name: 'serial_number', length: 150, unique: true)]
    private ?string $serialNumber = null;

    #[ORM\Column(length: 150)]
    private ?string $model = null;

    #[ORM\Column(length: 100)]
    private ?string $department = null;

    #[ORM\Column(name: 'purchase_date', type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $purchaseDate = null;

    #[ORM\Column(name: 'warranty_expiry', type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $warrantyExpiry = null;

    #[ORM\Column(name: 'office_location', length: 150)]
    private ?string $officeLocation = null;

    #[ORM\Column(length: 20, options: ['default' => 'Active'])]
    private string $status = 'Active';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAssetId(): ?string
    {
        return $this->assetId;
    }

    public function setAssetId(string $assetId): static
    {
        $this->assetId = $assetId;

        return $this;
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function setUserName(string $userName): static
    {
        $this->userName = $userName;

        return $this;
    }

    public function getPcName(): ?string
    {
        return $this->pcName;
    }

    public function setPcName(string $pcName): static
    {
        $this->pcName = $pcName;

        return $this;
    }

    public function getSerialNumber(): ?string
    {
        return $this->serialNumber;
    }

    public function setSerialNumber(string $serialNumber): static
    {
        $this->serialNumber = $serialNumber;

        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(string $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(string $department): static
    {
        $this->department = $department;

        return $this;
    }

    public function getPurchaseDate(): ?\DateTimeImmutable
    {
        return $this->purchaseDate;
    }

    public function setPurchaseDate(?\DateTimeImmutable $purchaseDate): static
    {
        $this->purchaseDate = $purchaseDate;

        return $this;
    }

    public function getWarrantyExpiry(): ?\DateTimeImmutable
    {
        return $this->warrantyExpiry;
    }

    public function setWarrantyExpiry(?\DateTimeImmutable $warrantyExpiry): static
    {
        $this->warrantyExpiry = $warrantyExpiry;

        return $this;
    }

    public function getOfficeLocation(): ?string
    {
        return $this->officeLocation;
    }

    public function setOfficeLocation(string $officeLocation): static
    {
        $this->officeLocation = $officeLocation;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * @param string|null $notes
     */
    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }
}
