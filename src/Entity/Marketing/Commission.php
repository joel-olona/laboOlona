<?php

namespace App\Entity\Marketing;

use App\Entity\User;
use App\Repository\Marketing\CommissionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommissionRepository::class)]
class Commission
{
    const STATUS_PENDING = 'PENDING';
    const STATUS_FAILED = 'FAILED';
    const STATUS_CANCELLED = 'CANCELLED';
    const STATUS_ON_HOLD = 'ON_HOLD';
    const STATUS_PROCESSING = 'PROCESSING';
    const STATUS_AUTHORIZED = 'AUTHORIZED';
    const STATUS_DISPUTED = 'DISPUTED';
    const DOC_DOWNLOAD = 'documents/';
    const TYPE_ONE_TIME = 'ONE_TIME';
    const TYPE_SUBSCRIPTION = 'SUBSCRIPTION';

    public static function getStatuses() {
        return [
            'En attente' => self::STATUS_PENDING ,
            'Échouée' => self::STATUS_FAILED ,
            'Annulée' => self::STATUS_CANCELLED ,
            'Ouverte' => self::STATUS_ON_HOLD ,
            'En traitement' => self::STATUS_PROCESSING ,
            'Autorisée' => self::STATUS_AUTHORIZED ,
            'Contestée' => self::STATUS_DISPUTED ,
        ];
    }
    
    public static function getLabels() {
        return [
            self::STATUS_PENDING =>         '<span class="badge rounded-pill bg-secondary">En attente</span>' ,
            self::STATUS_FAILED =>          '<span class="badge rounded-pill bg-dark">Échouée</span>' ,
            self::STATUS_CANCELLED =>       '<span class="badge rounded-pill bg-danger">Annulée</span>' ,
            self::STATUS_ON_HOLD =>         '<span class="badge rounded-pill bg-info">Ouverte</span>' ,
            self::STATUS_PROCESSING =>      '<span class="badge rounded-pill bg-warning">En traitement</span>' ,
            self::STATUS_AUTHORIZED =>      '<span class="badge rounded-pill bg-success">Autorisée</span>' ,
            self::STATUS_DISPUTED =>        '<span class="badge rounded-pill bg-danger">Contestée</span>' ,
        ];
    }

    public static function getTypes() {
        return [
            'Ponctuel' => self::TYPE_ONE_TIME ,
            'Abonnement' => self::TYPE_SUBSCRIPTION ,
        ];
    }
    
    public static function getTypeLabels() {
        return [
            self::TYPE_ONE_TIME =>         '<span class="badge bg-primary-subtle text-primary-emphasis rounded-pill">Ponctuel</span>' ,
            self::TYPE_SUBSCRIPTION =>          '<span class="badge bg-warning-subtle text-warning-emphasis rounded-pill">Abonnement</span>' ,
        ];
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?float $amount = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $saleReference = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'commissions')]
    private ?User $user = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $serviceType = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $salesPeriod = null;

    #[ORM\Column(nullable: true)]
    private ?float $fixedAmount = null;

    #[ORM\Column(scale: 2, nullable: true)]
    private ?float $commissionPercentage = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->status = self::STATUS_PENDING;
        $this->serviceType = self::TYPE_ONE_TIME;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(?float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getSaleReference(): ?string
    {
        return $this->saleReference;
    }

    public function setSaleReference(?string $saleReference): static
    {
        $this->saleReference = $saleReference;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getServiceType(): ?string
    {
        return $this->serviceType;
    }

    public function setServiceType(?string $serviceType): static
    {
        $this->serviceType = $serviceType;

        return $this;
    }

    public function getSalesPeriod(): ?string
    {
        return $this->salesPeriod;
    }

    public function setSalesPeriod(?string $salesPeriod): static
    {
        $this->salesPeriod = $salesPeriod;

        return $this;
    }

    public function getFixedAmount(): ?float
    {
        return $this->fixedAmount;
    }

    public function setFixedAmount(?float $fixedAmount): static
    {
        $this->fixedAmount = $fixedAmount;

        return $this;
    }

    public function getCommissionPercentage(): ?float
    {
        return $this->commissionPercentage;
    }

    public function setCommissionPercentage(?float $commissionPercentage): static
    {
        $this->commissionPercentage = $commissionPercentage;

        return $this;
    }
}
