<?php

namespace App\Game;

use App\Entity\User;

final class CreateGame
{
    private $home;
    private $away;
    private $place;
    private $datetime;
    private $user;

    public function __construct(string $home, string $away, string $place, \DateTime $dateTime, User $user){
        $this->home = $home;
        $this->away = $away;
        $this->place = $place;
        $this->datetime = $dateTime;
        $this->user = $user;
    }

    public function getHome(): string
    {
        return $this->home;
    }

    public function getAway(): string
    {
        return $this->away;
    }

    public function getPlace(): string
    {
        return $this->place;
    }

    public function getDatetime(): \DateTime
    {
        return $this->datetime;
    }

    public function getUser(): User
    {
        return $this->user;
    }


}