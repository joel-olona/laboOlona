<?php

namespace App\Entity\Formation;

use App\Entity\User;
use DateTimeInterface;
use App\Entity\Vues\VideoVues;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use App\Repository\Formation\VideoRepository;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: VideoRepository::class)]
class Video
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $url = null;

    #[ORM\Column(nullable: true)]
    private ?int $duree = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $publieeLe = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $miniature = null;

    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?string $nombreVues = null;

    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?string $nombreLikes = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $auteur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $langue = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $quality = null;

    #[ORM\Column(nullable: true)]
    private ?array $metadata = null;

    #[ORM\ManyToOne(inversedBy: 'videos')]
    private ?Playlist $playlist = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $duration = null;

    #[ORM\OneToMany(mappedBy: 'video', targetEntity: VideoVues::class)]
    private Collection $videoVues;

    public function __construct()
    {
        $this->videoVues = new ArrayCollection();
    }

    public function __toString(){
        return $this->getTitre();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

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

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getDuree(): ?int
    {
        return $this->duree;
    }

    public function setDuree(?int $duree): static
    {
        $this->duree = $duree;

        return $this;
    }

    public function getPublieeLe(): ?\DateTimeInterface
    {
        return $this->publieeLe;
    }

    public function setPublieeLe(\DateTimeInterface $publieeLe): static
    {
        $this->publieeLe = $publieeLe;

        return $this;
    }

    public function getMiniature(): ?string
    {
        return $this->miniature;
    }

    public function setMiniature(?string $miniature): static
    {
        $this->miniature = $miniature;

        return $this;
    }

    public function getNombreVues(): ?string
    {
        return $this->nombreVues;
    }

    public function setNombreVues(?string $nombreVues): static
    {
        $this->nombreVues = $nombreVues;

        return $this;
    }

    public function getNombreLikes(): ?string
    {
        return $this->nombreLikes;
    }

    public function setNombreLikes(?string $nombreLikes): static
    {
        $this->nombreLikes = $nombreLikes;

        return $this;
    }

    public function getAuteur(): ?string
    {
        return $this->auteur;
    }

    public function setAuteur(?string $auteur): static
    {
        $this->auteur = $auteur;

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

    public function getLangue(): ?string
    {
        return $this->langue;
    }

    public function setLangue(?string $langue): static
    {
        $this->langue = $langue;

        return $this;
    }

    public function getQuality(): ?string
    {
        return $this->quality;
    }

    public function setQuality(?string $quality): static
    {
        $this->quality = $quality;

        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getPlaylist(): ?Playlist
    {
        return $this->playlist;
    }

    public function setPlaylist(?Playlist $playlist): static
    {
        $this->playlist = $playlist;

        return $this;
    }

    public function getCustomId(): ?string
    {
        return $this->customId;
    }

    public function setCustomId(?string $customId): static
    {
        $this->customId = $customId;

        return $this;
    }

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function setDuration(?string $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * @return Collection<int, VideoVues>
     */
    public function getVideoVues(): Collection
    {
        return $this->videoVues;
    }

    public function addVideoVue(VideoVues $videoVue): static
    {
        if (!$this->videoVues->contains($videoVue)) {
            $this->videoVues->add($videoVue);
            $videoVue->setVideo($this);
        }

        return $this;
    }

    public function removeVideoVue(VideoVues $videoVue): static
    {
        if ($this->videoVues->removeElement($videoVue)) {
            // set the owning side to null (unless already changed)
            if ($videoVue->getVideo() === $this) {
                $videoVue->setVideo(null);
            }
        }

        return $this;
    }

    /**
     * Vérifie si un utilisateur donné a visionné cette vidéo.
     *
     * @param User $user L'utilisateur à vérifier.
     * @return bool Retourne vrai si l'utilisateur a visionné la vidéo, sinon faux.
     */
    public function estVisionneePar(User $user): bool
    {
        foreach ($this->videoVues as $view) {
            if ($view->getUser() === $user) {
                return true;
            }
        }

        return false;
    }
    
    /**
     * Retourne la date à laquelle l'utilisateur donné a visionné la vidéo.
     *
     * @param User $user L'utilisateur à vérifier.
     * @return DateTimeInterface|null La date de la vue si l'utilisateur a visionné la vidéo, sinon null.
     */
    public function getDateVisionneePar(User $user): ?DateTimeInterface
    {
        $dateView = null;
        foreach ($this->videoVues as $view) {
            if ($view->getUser() === $user) {
                if($view->getUpdateAt() !== null){
                    $dateView = $view->getUpdateAt();
                }else{
                    $dateView = $view->getCreatedAt();
                }
            }
        }

        return $dateView;
    }
}
