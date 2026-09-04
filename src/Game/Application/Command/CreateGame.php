<?php

namespace App\Game\Application\Command;

use App\Shared\Domain\Command;
use Symfony\Component\Uid\Uuid;

/**
 * Carries the owner by id, not as an entity: a command has to survive being
 * put on a queue, and an entity does not.
 */
final class CreateGame implements Command
{
    public function __construct(
        private string $home,
        private string $away,
        private string $location,
        private \DateTimeInterface $datetime,
        private Uuid $ownerId,
    ) {}

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

    public function getOwnerId(): Uuid
    {
        return $this->ownerId;
    }
}
