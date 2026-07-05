<?php

namespace App\Game\Application\Command;

use App\Game\Infrastructure\GameProjector;
use App\Repository\GameRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class DeleteGameHandler
{
    public function __construct(private GameRepository $gameRepository, private GameProjector $gameProjector) {}

    public function __invoke(DeleteGame $deleteGame)
    {
        $game = $this->gameRepository->find($deleteGame->getGameId());
        $this->gameRepository->remove($game, true);

        $this->gameProjector->projectReadModels();
    }
}
