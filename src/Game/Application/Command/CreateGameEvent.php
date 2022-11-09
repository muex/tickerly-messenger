<?php

namespace App\Game\Application\Command;

use App\Entity\Game;
use App\Shared\Domain\Command;

final class CreateGameEvent implements Command
{
    private $game;
    private $timecode;
    private $message;

    public function __construct(Game $game, string $timecode, string $message){
        $this->game = $game;
        $this->timecode = $timecode;
        $this->message = $message;
    }

    public function getGame(): Game
    {
        return $this->game;
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