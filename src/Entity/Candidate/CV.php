<?php

namespace App\Entity\Candidate;

use App\Entity\CandidateProfile;
use App\Repository\Candidate\CVRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CVRepository::class)]
class CV
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $cvLink = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $uploadedAt = null;

    #[ORM\ManyToOne(inversedBy: 'cvs')]
    private ?CandidateProfile $candidat = null;

    #[ORM\Column(length: 255)]
    private ?string $safeFileName = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCvLink(): ?string
    {
        return $this->cvLink;
    }

    public function setCvLink(string $cvLink): static
    {
        $this->cvLink = $cvLink;

        return $this;
    }

    public function getUploadedAt(): ?\DateTimeInterface
    {
        return $this->uploadedAt;
    }

    public function setUploadedAt(\DateTimeInterface $uploadedAt): static
    {
        $this->uploadedAt = $uploadedAt;

        return $this;
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

    public function getSafeFileName(): ?string
    {
        return $this->safeFileName;
    }

    public function setSafeFileName(string $safeFileName): static
    {
        $this->safeFileName = $safeFileName;

        return $this;
    }
}
