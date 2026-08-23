<?php

namespace App\Game\Application\Command;

use App\Shared\Domain\Command;

/**
 * Activates or deactivates a game. Issued from the admin area.
 */
final class SetGameActive implements Command
{
    public function __construct(private int $gameId, private bool $active) {}

    public function getGameId(): int
    {
        return $this->gameId;
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}
