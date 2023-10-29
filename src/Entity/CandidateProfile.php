<?php

namespace App\Entity;

use App\Entity\Candidate\Applications;
use App\Entity\Candidate\Competences;
use App\Entity\Candidate\Experiences;
use App\Entity\Moderateur\Metting;
use App\Repository\CandidateProfileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CandidateProfileRepository::class)]
class CandidateProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'candidateProfile', cascade: ['persist', 'remove'])]
    private ?User $candidat = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $resume = null;

    #[ORM\ManyToMany(targetEntity: Competences::class, mappedBy: 'profil')]
    private Collection $competences;

    #[ORM\OneToMany(mappedBy: 'profil', targetEntity: Experiences::class)]
    private Collection $experiences;

    #[ORM\OneToMany(mappedBy: 'candidat', targetEntity: Applications::class)]
    private Collection $applications;

    #[ORM\OneToMany(mappedBy: 'candidat', targetEntity: Metting::class)]
    private Collection $mettings;

    public function __construct()
    {
        $this->competences = new ArrayCollection();
        $this->experiences = new ArrayCollection();
        $this->applications = new ArrayCollection();
        $this->mettings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCandidat(): ?User
    {
        return $this->candidat;
    }

    public function setCandidat(?User $candidat): static
    {
        $this->candidat = $candidat;

        return $this;
    }

    public function getResume(): ?string
    {
        return $this->resume;
    }

    public function setResume(?string $resume): static
    {
        $this->resume = $resume;

        return $this;
    }

    /**
     * @return Collection<int, Competences>
     */
    public function getCompetences(): Collection
    {
        return $this->competences;
    }

    public function addCompetence(Competences $competence): static
    {
        if (!$this->competences->contains($competence)) {
            $this->competences->add($competence);
            $competence->addProfil($this);
        }

        return $this;
    }

    public function removeCompetence(Competences $competence): static
    {
        if ($this->competences->removeElement($competence)) {
            $competence->removeProfil($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Experiences>
     */
    public function getExperiences(): Collection
    {
        return $this->experiences;
    }

    public function addExperience(Experiences $experience): static
    {
        if (!$this->experiences->contains($experience)) {
            $this->experiences->add($experience);
            $experience->setProfil($this);
        }

        return $this;
    }

    public function removeExperience(Experiences $experience): static
    {
        if ($this->experiences->removeElement($experience)) {
            // set the owning side to null (unless already changed)
            if ($experience->getProfil() === $this) {
                $experience->setProfil(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Applications>
     */
    public function getApplications(): Collection
    {
        return $this->applications;
    }

    public function addApplication(Applications $application): static
    {
        if (!$this->applications->contains($application)) {
            $this->applications->add($application);
            $application->setCandidat($this);
        }

        return $this;
    }

    public function removeApplication(Applications $application): static
    {
        if ($this->applications->removeElement($application)) {
            // set the owning side to null (unless already changed)
            if ($application->getCandidat() === $this) {
                $application->setCandidat(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Metting>
     */
    public function getMettings(): Collection
    {
        return $this->mettings;
    }

    public function addMetting(Metting $metting): static
    {
        if (!$this->mettings->contains($metting)) {
            $this->mettings->add($metting);
            $metting->setCandidat($this);
        }

        return $this;
    }

    public function removeMetting(Metting $metting): static
    {
        if ($this->mettings->removeElement($metting)) {
            // set the owning side to null (unless already changed)
            if ($metting->getCandidat() === $this) {
                $metting->setCandidat(null);
            }
        }

        return $this;
    }
}
