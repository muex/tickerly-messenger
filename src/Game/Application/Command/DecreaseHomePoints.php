<?php

namespace App\Game\Application\Command;

class DecreaseHomePoints
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