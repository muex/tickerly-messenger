<?php

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRepository::class)]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $location = null;

    #[ORM\Column(length: 255)]
    private ?string $home = null;

    #[ORM\Column(length: 255)]
    private ?string $away = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $datetime = null;

    #[ORM\Column(nullable: true)]
    private ?int $homepoints =0;

    #[ORM\Column(nullable: true)]
    private ?int $awaypoints = 0;

    #[ORM\OneToMany(mappedBy: 'game', targetEntity: GameEvent::class)]
    #[ORM\OrderBy(["timecode" => 'DESC'])]
    private Collection $gameEvents;

    #[ORM\ManyToOne(inversedBy: 'games')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    public function __construct()
    {
        $this->gameEvents = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getHome(): ?string
    {
        return $this->home;
    }

    public function setHome(string $home): self
    {
        $this->home = $home;

        return $this;
    }

    public function getAway(): ?string
    {
        return $this->away;
    }

    public function setAway(string $away): self
    {
        $this->away = $away;

        return $this;
    }

    public function getDatetime(): ?\DateTimeInterface
    {
        return $this->datetime;
    }

    public function setDatetime(\DateTimeInterface $datetime): self
    {
        $this->datetime = $datetime;

        return $this;
    }

    public function getHomepoints(): ?int
    {
        return $this->homepoints;
    }

    public function setHomepoints(?int $homepoints): self
    {
        $this->homepoints = $homepoints;

        return $this;
    }

    public function getAwaypoints(): ?int
    {
        return $this->awaypoints;
    }

    public function setAwaypoints(?int $awaypoints): self
    {
        $this->awaypoints = $awaypoints;

        return $this;
    }

    /**
     * @return Collection<int, GameEvent>
     */
    public function getGameEvents(): Collection
    {
        return $this->gameEvents;
    }

    public function addGameEvent(GameEvent $gameEvent): self
    {
        if (!$this->gameEvents->contains($gameEvent)) {
            $this->gameEvents->add($gameEvent);
            $gameEvent->setGame($this);
        }

        return $this;
    }

    public function removeGameEvent(GameEvent $gameEvent): self
    {
        if ($this->gameEvents->removeElement($gameEvent)) {
            // set the owning side to null (unless already changed)
            if ($gameEvent->getGame() === $this) {
                $gameEvent->setGame(null);
            }
        }

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }
}
