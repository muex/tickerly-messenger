<?php

namespace App\Game\Application\Command;

use App\Entity\Game;
use App\Game\Application\Event\GameStateChanged;
use App\Game\Domain\GameSlugger;
use App\Repository\GameRepository;
use App\Shared\Domain\EventBus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class CreateGameHandler
{
    public function __construct(
        private GameRepository $gameRepository,
        private GameSlugger $gameSlugger,
        private EventBus $eventBus,
    ) {}

    public function __invoke(CreateGame $game)
    {
        $newGame = new Game();
        $newGame->setOwner($game->getOwner());
        $newGame->setHome($game->getHome());
        $newGame->setAway($game->getAway());
        $newGame->setLocation($game->getLocation());
        $newGame->setDatetime($game->getDatetime());
        $newGame->setSlug($this->gameSlugger->slugFor($game->getHome(), $game->getAway(), $game->getDatetime()));

        $this->gameRepository->save($newGame, true);

        $this->eventBus->dispatch(new GameStateChanged());
    }
}