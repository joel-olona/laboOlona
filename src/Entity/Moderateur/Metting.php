<?php

namespace App\Entity\Moderateur;

use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use App\Entity\Enum\StatusMetting;
use App\Entity\ModerateurProfile;
use App\Repository\Moderateur\MettingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MettingRepository::class)]
class Metting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'mettings')]
    private ?EntrepriseProfile $entreprise = null;

    #[ORM\ManyToOne(inversedBy: 'mettings')]
    private ?CandidateProfile $candidat = null;

    #[ORM\ManyToOne(inversedBy: 'mettings')]
    private ?ModerateurProfile $moderateur = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateRendezVous = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lieu = null;

    #[ORM\Column(enumType: StatusMetting::class)]
    private ?string $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntreprise(): ?EntrepriseProfile
    {
        return $this->entreprise;
    }

    public function setEntreprise(?EntrepriseProfile $entreprise): static
    {
        $this->entreprise = $entreprise;

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

    public function getModerateur(): ?ModerateurProfile
    {
        return $this->moderateur;
    }

    public function setModerateur(?ModerateurProfile $moderateur): static
    {
        $this->moderateur = $moderateur;

        return $this;
    }

    public function getDateRendezVous(): ?\DateTimeInterface
    {
        return $this->dateRendezVous;
    }

    public function setDateRendezVous(\DateTimeInterface $dateRendezVous): static
    {
        $this->dateRendezVous = $dateRendezVous;

        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(?string $lieu): static
    {
        $this->lieu = $lieu;

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

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }
}
