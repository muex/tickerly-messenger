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
        $nextGames = array();
        $gamesCollection = $this->gameRepository->findNextGames();

        foreach ($gamesCollection as $game)
        {
            $nextGames[] = array(
                'id' => $game->getId(),
                'home' => $game->getHome(),
                'away' => $game->getAway(),
                'location' => $game->getLocation(),
                'datetime' => $game->getDatetime(),
                'homepoints' => $game->getHomepoints(),
                'awaypoints' => $game->getAwaypoints()
            );
        }

        file_put_contents('nextgames.json', json_encode($nextGames));

        $lastGames = array();
        $gamesCollection = $this->gameRepository->findLastGames();

        foreach ($gamesCollection as $game)
        {
            $lastGames[] = array(
                'id' => $game->getId(),
                'home' => $game->getHome(),
                'away' => $game->getAway(),
                'location' => $game->getLocation(),
                'datetime' => $game->getDatetime(),
                'homepoints' => $game->getHomepoints(),
                'awaypoints' => $game->getAwaypoints()
            );
        }

        file_put_contents('lastgames.json', json_encode($lastGames));
/*
        $nextGames = array();
        $gamesCollection = $this->gameRepository->findNextGames();

        foreach ($gamesCollection as $game)
        {
            $nextGames[] = array(
                'id' => $game->getId(),
                'home' => $game->getHome(),
                'away' => $game->getAway(),
                'location' => $game->getLocation(),
                'datetime' => $game->getDatetime()
            );
        }

        file_put_contents('nextgames.json', json_encode($nextGames));
*/
    }
}