<?php

namespace App\Game\Application\Command;

use App\Shared\Domain\Command;
use Symfony\Component\Uid\Uuid;

final class UpdateGame implements Command
{
    private $home;
    private $away;
    private $location;
    private $datetime;

    public function __construct(private Uuid $gameId, string $home, string $away, string $location, \DateTime $dateTime){
        $this->home = $home;
        $this->away = $away;
        $this->location = $location;
        $this->datetime = $dateTime;
    }

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

    public function getDatetime(): \DateTime
    {
        return $this->datetime;
    }
}
