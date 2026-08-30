<?php

namespace App\Game\Application\Command;

use App\Shared\Domain\Command;
use Symfony\Component\Uid\Uuid;

/**
 * Blows the final whistle on a game, or takes it back. Issued by the owner
 * from the game page.
 */
final class SetGameFinished implements Command
{
    public function __construct(private Uuid $gameId, private bool $finished) {}

    public function getGameId(): Uuid
    {
        return $this->gameId;
    }

    public function isFinished(): bool
    {
        return $this->finished;
    }
}
