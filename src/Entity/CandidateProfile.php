<?php

namespace App\Entity;

use App\Entity\Candidate\Langages;
use App\Entity\Candidate\Social;
use App\Entity\Vues\CandidatVues;
use Serializable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Moderateur\Metting;
use App\Entity\Candidate\Competences;
use App\Entity\Candidate\Experiences;
use App\Entity\Candidate\Applications;
use Doctrine\Common\Collections\Collection;
use App\Repository\CandidateProfileRepository;
use DateTime;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\Common\Collections\ArrayCollection;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: CandidateProfileRepository::class)]
#[Vich\Uploadable]
class CandidateProfile implements Serializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'candidateProfile', cascade: ['persist', 'remove'])]
    private ?User $candidat = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $resume = null;

    #[ORM\ManyToMany(targetEntity: Competences::class, mappedBy: 'profil', cascade: ['persist', 'remove'])]
    private Collection $competences;

    #[ORM\OneToMany(mappedBy: 'profil', targetEntity: Experiences::class, cascade: ['persist', 'remove'])]
    private Collection $experiences;

    #[ORM\OneToMany(mappedBy: 'candidat', targetEntity: Applications::class, cascade: ['persist', 'remove'])]
    private Collection $applications;

    #[ORM\OneToMany(mappedBy: 'candidat', targetEntity: Metting::class, cascade: ['persist', 'remove'])]
    private Collection $mettings;

    #[ORM\ManyToMany(targetEntity: Secteur::class,  inversedBy: 'canditat')]
    private Collection $secteurs;

    #[Vich\UploadableField(mapping: 'cv_expert', fileNameProperty: 'fileName')]
    private ?File $file = null;
    
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fileName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $localisation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $birthday = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $titre = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cv = null;

    #[ORM\OneToMany(mappedBy: 'profile', targetEntity: Langages::class, cascade: ['persist', 'remove'])]
    private Collection $langages;

    #[ORM\OneToOne(mappedBy: 'candidat', cascade: ['persist', 'remove'])]
    private ?Social $social = null;

    #[ORM\OneToMany(mappedBy: 'candidat', targetEntity: CandidatVues::class)]
    private Collection $vues;

    public function __construct()
    {
        $this->competences = new ArrayCollection();
        $this->experiences = new ArrayCollection();
        $this->applications = new ArrayCollection();
        $this->mettings = new ArrayCollection();
        $this->secteurs = new ArrayCollection();
        $this->createdAt = new DateTime();
        $this->langages = new ArrayCollection();
        $this->vues = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getCandidat()->getNom();
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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

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

    /**
     * @return Collection<int, Secteur>
     */
    public function getSecteurs(): Collection
    {
        return $this->secteurs;
    }

    public function addSecteur(Secteur $secteur): static
    {
        if (!$this->secteurs->contains($secteur)) {
            $this->secteurs->add($secteur);
            $secteur->addCandidat($this);
        }

        return $this;
    }

    public function removeSecteur(Secteur $secteur): static
    {
        if ($this->secteurs->removeElement($secteur)) {
            $secteur->removeCandidat($this);
        }

        return $this;
    }

    public function setFile(?File $file = null): void
    {
        $this->file = $file;

        if (null !== $file) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTime();
        }

    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(?string $fileName): static
    {
        $this->fileName = $fileName;

        return $this;
    }
    
    public function serialize()
    {
        $this->fileName = base64_encode($this->fileName);
    }

    public function unserialize($serialized)
    {
        $this->fileName = base64_decode($this->fileName);

    }

    public function getLocalisation(): ?string
    {
        return $this->localisation;
    }

    public function setLocalisation(?string $localisation): static
    {
        $this->localisation = $localisation;

        return $this;
    }

    public function getBirthday(): ?\DateTimeInterface
    {
        return $this->birthday;
    }

    public function setBirthday(?\DateTimeInterface $birthday): static
    {
        $this->birthday = $birthday;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getCv(): ?string
    {
        return $this->cv;
    }

    public function setCv(?string $cv): static
    {
        $this->cv = $cv;

        return $this;
    }

    /**
     * @return Collection<int, Langages>
     */
    public function getLangages(): Collection
    {
        return $this->langages;
    }

    public function addLangage(Langages $langage): static
    {
        if (!$this->langages->contains($langage)) {
            $this->langages->add($langage);
            $langage->setProfile($this);
        }

        return $this;
    }

    public function removeLangage(Langages $langage): static
    {
        if ($this->langages->removeElement($langage)) {
            // set the owning side to null (unless already changed)
            if ($langage->getProfile() === $this) {
                $langage->setProfile(null);
            }
        }

        return $this;
    }

    public function getSocial(): ?Social
    {
        return $this->social;
    }

    public function setSocial(?Social $social): static
    {
        // unset the owning side of the relation if necessary
        if ($social === null && $this->social !== null) {
            $this->social->setCandidat(null);
        }

        // set the owning side of the relation if necessary
        if ($social !== null && $social->getCandidat() !== $this) {
            $social->setCandidat($this);
        }

        $this->social = $social;

        return $this;
    }

    /**
     * @return Collection<int, CandidatVues>
     */
    public function getVues(): Collection
    {
        return $this->vues;
    }

    public function addVue(CandidatVues $vue): static
    {
        if (!$this->vues->contains($vue)) {
            $this->vues->add($vue);
            $vue->setCandidat($this);
        }

        return $this;
    }

    public function removeVue(CandidatVues $vue): static
    {
        if ($this->vues->removeElement($vue)) {
            // set the owning side to null (unless already changed)
            if ($vue->getCandidat() === $this) {
                $vue->setCandidat(null);
            }
        }

        return $this;
    }
}
