<?php

namespace App\Game\Application\Command;

use App\Entity\Game;
use App\Game\Application\Event\GameStateChanged;
use App\Game\Domain\GameSlugger;
use App\Repository\GameRepository;
use App\Repository\UserRepository;
use App\Shared\Domain\EventBus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class CreateGameHandler
{
    public function __construct(
        private GameRepository $gameRepository,
        private UserRepository $userRepository,
        private GameSlugger $gameSlugger,
        private EventBus $eventBus,
    ) {}

    public function __invoke(CreateGame $game)
    {
        $owner = $this->userRepository->find($game->getOwnerId());

        if ($owner === null) {
            throw new \RuntimeException('Cannot create a game for a user that no longer exists.');
        }

        $newGame = new Game();
        $newGame->setOwner($owner);
        $newGame->setHome($game->getHome());
        $newGame->setAway($game->getAway());
        $newGame->setLocation($game->getLocation());
        $newGame->setDatetime($game->getDatetime());
        $newGame->setSlug($this->gameSlugger->slugFor($game->getHome(), $game->getAway(), $game->getDatetime()));

        $this->gameRepository->save($newGame, true);

        $this->eventBus->dispatch(new GameStateChanged($newGame->getSlug()));
    }
}
