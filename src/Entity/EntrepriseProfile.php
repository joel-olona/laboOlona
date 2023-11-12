<?php

namespace App\Entity;

use App\Entity\Entreprise\JobListing;
use App\Entity\Enum\TailleEntreprise;
use App\Entity\Moderateur\Metting;
use App\Repository\EntrepriseProfileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EntrepriseProfileRepository::class)]
class EntrepriseProfile
{
    const SIZE_SMALL = 'SM';
    const SIZE_MEDIUM = 'MD';
    const SIZE_LARGE = 'LG';

    const STATUS_VALID = 'VALID';
    const STATUS_PENDING = 'PENDING';
    const STATUS_PREMIUM = 'PREMIUM';
    const STATUS_BANNED = 'BANNED';

    const CHOICE_SIZE = [        
         'Petite (1-10 employés)' => self::SIZE_SMALL ,
         'Moyenne (11-100 employés)' => self::SIZE_MEDIUM ,
         'Grande (plus de 100 employés)' => self::SIZE_LARGE ,
    ];

    const CHOICE_STATUS = [        
        'Valide' => self::STATUS_VALID,
        'En attente' => self::STATUS_PENDING,
        'Premium' => self::STATUS_PREMIUM,
        'Banni' => self::STATUS_BANNED,
    ];
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'entrepriseProfile', cascade: ['persist', 'remove'])]
    private ?User $entreprise = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $taille = null;

    #[ORM\Column(length: 255)]
    private ?string $localisation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $siteWeb = null;

    #[ORM\OneToMany(mappedBy: 'entreprise', targetEntity: JobListing::class)]
    private Collection $jobListings;

    #[ORM\OneToMany(mappedBy: 'entreprise', targetEntity: Metting::class)]
    private Collection $mettings;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToMany(targetEntity: Secteur::class, mappedBy: 'entreprise')]
    private Collection $secteurs;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    public function __construct()
    {
        $this->jobListings = new ArrayCollection();
        $this->mettings = new ArrayCollection();
        $this->secteurs = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getEntreprise()->getNom();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntreprise(): ?User
    {
        return $this->entreprise;
    }

    public function setEntreprise(?User $entreprise): static
    {
        $this->entreprise = $entreprise;

        return $this;
    }

    public function getTaille(): ?string
    {
        return $this->taille;
    }

    public function setTaille(?string $taille): static
    {
        $this->taille = $taille;

        return $this;
    }

    public function getLocalisation(): ?string
    {
        return $this->localisation;
    }

    public function setLocalisation(string $localisation): static
    {
        $this->localisation = $localisation;

        return $this;
    }

    public function getSiteWeb(): ?string
    {
        return $this->siteWeb;
    }

    public function setSiteWeb(?string $siteWeb): static
    {
        $this->siteWeb = $siteWeb;

        return $this;
    }

    /**
     * @return Collection<int, JobListing>
     */
    public function getJobListings(): Collection
    {
        return $this->jobListings;
    }

    public function addJobListing(JobListing $jobListing): static
    {
        if (!$this->jobListings->contains($jobListing)) {
            $this->jobListings->add($jobListing);
            $jobListing->setEntreprise($this);
        }

        return $this;
    }

    public function removeJobListing(JobListing $jobListing): static
    {
        if ($this->jobListings->removeElement($jobListing)) {
            // set the owning side to null (unless already changed)
            if ($jobListing->getEntreprise() === $this) {
                $jobListing->setEntreprise(null);
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
            $metting->setEntreprise($this);
        }

        return $this;
    }

    public function removeMetting(Metting $metting): static
    {
        if ($this->mettings->removeElement($metting)) {
            // set the owning side to null (unless already changed)
            if ($metting->getEntreprise() === $this) {
                $metting->setEntreprise(null);
            }
        }

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
            $secteur->addEntreprise($this);
        }

        return $this;
    }

    public function removeSecteur(Secteur $secteur): static
    {
        if ($this->secteurs->removeElement($secteur)) {
            $secteur->removeEntreprise($this);
        }

        return $this;
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }
}
