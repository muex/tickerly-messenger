<?php

namespace App\Game\Application\Command;

use App\Game\Infrastructure\GameProjector;
use App\Repository\GameRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class UpdateGameHandler
{
    public function __construct(private GameRepository $gameRepository, private GameProjector $gameProjector) {}

    public function __invoke(UpdateGame $updateGame)
    {
        $game = $this->gameRepository->find($updateGame->getGameId());
        $game->setHome($updateGame->getHome());
        $game->setAway($updateGame->getAway());
        $game->setLocation($updateGame->getLocation());
        $game->setDatetime($updateGame->getDatetime());

        $this->gameRepository->save($game, true);

        $this->gameProjector->projectReadModels();
    }
}
