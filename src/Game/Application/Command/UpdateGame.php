<?php

namespace App\Game\Application\Command;

use App\Shared\Domain\Command;
use Symfony\Component\Uid\Uuid;

final class UpdateGame implements Command
{
    public function __construct(
        private Uuid $gameId,
        private string $home,
        private string $away,
        private string $location,
        private \DateTimeInterface $datetime,
    ) {}

    public function getGameId(): Uuid
    {
        return $this->gameId;
    }

    public function getHome(): string
    {
        return $this->home;
    }

    public function getAway(): string
    {
        return $this->away;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getDatetime(): \DateTimeInterface
    {
        return $this->datetime;
    }
}
