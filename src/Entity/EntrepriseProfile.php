<?php

namespace App\Entity;

use App\Entity\Entreprise\JobListing;
use App\Entity\Enum\TailleEntreprise;
use App\Entity\Moderateur\Metting;
use App\Repository\EntrepriseProfileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EntrepriseProfileRepository::class)]
class EntrepriseProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'entrepriseProfile', cascade: ['persist', 'remove'])]
    private ?User $entreprise = null;

    #[ORM\Column(enumType: TailleEntreprise::class)]
    private ?string $taille = null;

    #[ORM\Column(length: 255)]
    private ?string $localisation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $siteWeb = null;

    #[ORM\OneToMany(mappedBy: 'entreprise', targetEntity: JobListing::class)]
    private Collection $jobListings;

    #[ORM\OneToMany(mappedBy: 'entreprise', targetEntity: Metting::class)]
    private Collection $mettings;

    public function __construct()
    {
        $this->jobListings = new ArrayCollection();
        $this->mettings = new ArrayCollection();
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
}
