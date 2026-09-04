<?php

namespace App\Game\Application\Command;

use App\Shared\Domain\Command;
use Symfony\Component\Uid\Uuid;

/**
 * Carries the game by id, not as an entity: a command has to survive being put
 * on a queue, and the handler is the one that should load what it writes to.
 */
final class CreateGameEvent implements Command
{
    public function __construct(
        private Uuid $gameId,
        private string $timecode,
        private string $message,
    ) {}

    public function getGameId(): Uuid
    {
        return $this->gameId;
    }

    public function getTimecode(): string
    {
        return $this->timecode;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
