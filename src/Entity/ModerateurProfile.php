<?php

namespace App\Entity;

use App\Entity\Moderateur\Metting;
use App\Repository\ModerateurProfileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ModerateurProfileRepository::class)]
class ModerateurProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'moderateurProfile', cascade: ['persist', 'remove'])]
    private ?User $moderateur = null;

    #[ORM\OneToMany(mappedBy: 'moderateur', targetEntity: Metting::class)]
    private Collection $mettings;

    public function __construct()
    {
        $this->mettings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModerateur(): ?User
    {
        return $this->moderateur;
    }

    public function setModerateur(?User $moderateur): static
    {
        $this->moderateur = $moderateur;

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
            $metting->setModerateur($this);
        }

        return $this;
    }

    public function removeMetting(Metting $metting): static
    {
        if ($this->mettings->removeElement($metting)) {
            // set the owning side to null (unless already changed)
            if ($metting->getModerateur() === $this) {
                $metting->setModerateur(null);
            }
        }

        return $this;
    }
}
