<?php

namespace App\Game\Application\Event;

use App\Repository\GameRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class GameCreatedHandler implements MessageHandlerInterface
{
    public function __construct(GameRepository $gameRepository)
    {
        $this->gameRepository = $gameRepository;
    }

    public function __invoke(GameCreated $event): void
    {
        $games = array();
        $gamesCollection = $this->gameRepository->findAll();

        foreach ($gamesCollection as $game)
        {
            $games[] = array(
                'id' => $game->getId(),
                'home' => $game->getHome(),
                'away' => $game->getAway(),
                'place' => $game->getPlace(),
                'datetime' => $game->getDatetime()
            );
        }
        //$games = array('a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5);
        file_put_contents('gamelist.json', json_encode($games));
    }
}