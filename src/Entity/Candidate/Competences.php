<?php

namespace App\Entity\Candidate;

use App\Entity\CandidateProfile;
use App\Repository\Candidate\CompetencesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompetencesRepository::class)]
class Competences
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToMany(targetEntity: CandidateProfile::class, inversedBy: 'competences')]
    private Collection $profil;

    public function __construct()
    {
        $this->profil = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

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

    /**
     * @return Collection<int, CandidateProfile>
     */
    public function getProfil(): Collection
    {
        return $this->profil;
    }

    public function addProfil(CandidateProfile $profil): static
    {
        if (!$this->profil->contains($profil)) {
            $this->profil->add($profil);
        }

        return $this;
    }

    public function removeProfil(CandidateProfile $profil): static
    {
        $this->profil->removeElement($profil);

        return $this;
    }
}
