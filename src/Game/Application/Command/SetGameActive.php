<?php

namespace App\Game\Application\Command;

use App\Shared\Domain\Command;
use Symfony\Component\Uid\Uuid;

/**
 * Activates or deactivates a game. Issued from the admin area.
 */
final class SetGameActive implements Command
{
    public function __construct(private Uuid $gameId, private bool $active) {}

    public function getGameId(): Uuid
    {
        return $this->gameId;
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}
