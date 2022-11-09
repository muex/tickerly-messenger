<?php

namespace App\Game\Application\Command;

use App\Entity\User;
use App\Shared\Domain\Command;

final class CreateGameEvent implements Command
{
    private $home;
    private $away;
    private $location;
    private $datetime;
    private $owner;

    public function __construct(string $home, string $away, string $location, \DateTime $dateTime, User $owner){
        $this->home = $home;
        $this->away = $away;
        $this->location = $location;
        $this->datetime = $dateTime;
        $this->owner = $owner;
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

    public function getOwner(): User
    {
        return $this->owner;
    }
}