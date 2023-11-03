<?php

namespace App\Entity\Candidate;

use App\Entity\CandidateProfile;
use App\Entity\Entreprise\JobListing;
use App\Entity\Enum\StatusApplication;
use App\Repository\Candidate\ApplicationsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApplicationsRepository::class)]
class Applications
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'applications')]
    private ?CandidateProfile $candidat = null;

    #[ORM\ManyToOne(targetEntity: JobListing::class)]
    private ?JobListing $jobListing = null;

    #[ORM\ManyToOne(inversedBy: 'applications')]
    private ?JobListing $annonce = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $lettreMotivation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cvLink = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCandidature = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $pretentionSalariale = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCandidat(): ?CandidateProfile
    {
        return $this->candidat;
    }

    public function setCandidat(?CandidateProfile $candidat): static
    {
        $this->candidat = $candidat;

        return $this;
    }

    public function getAnnonce(): ?JobListing
    {
        return $this->annonce;
    }

    public function setAnnonce(?JobListing $annonce): static
    {
        $this->annonce = $annonce;

        return $this;
    }

    public function getLettreMotivation(): ?string
    {
        return $this->lettreMotivation;
    }

    public function setLettreMotivation(string $lettreMotivation): static
    {
        $this->lettreMotivation = $lettreMotivation;

        return $this;
    }

    public function getCvLink(): ?string
    {
        return $this->cvLink;
    }

    public function setCvLink(?string $cvLink): static
    {
        $this->cvLink = $cvLink;

        return $this;
    }

    public function getDateCandidature(): ?\DateTimeInterface
    {
        return $this->dateCandidature;
    }

    public function setDateCandidature(\DateTimeInterface $dateCandidature): static
    {
        $this->dateCandidature = $dateCandidature;

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

    public function getPretentionSalariale(): ?string
    {
        return $this->pretentionSalariale;
    }

    public function setPretentionSalariale(string $pretentionSalariale): static
    {
        $this->pretentionSalariale = $pretentionSalariale;

        return $this;
    }
}
