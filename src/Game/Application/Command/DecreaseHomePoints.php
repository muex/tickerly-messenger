<?php

namespace App\Game\Application\Command;

use App\Shared\Domain\Command;

class DecreaseHomePoints implements Command
{
    private $gameId;

    public function __construct($gameId)
    {
        $this->gameId = $gameId;
    }

    public function getGameId()
    {
        return $this->gameId;
    }
}