<?php

namespace App\Game\Application\Command;

use App\Shared\Domain\Command;
use Symfony\Component\Uid\Uuid;

class DeleteGame implements Command
{
    public function __construct(private Uuid $gameId) {}

    public function getGameId(): Uuid
    {
        return $this->gameId;
    }
}