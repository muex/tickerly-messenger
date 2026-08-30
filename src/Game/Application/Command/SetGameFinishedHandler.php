<?php

namespace App\Game\Application\Command;

use App\Game\Application\Event\GameStateChanged;
use App\Repository\GameRepository;
use App\Shared\Domain\EventBus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class SetGameFinishedHandler
{
    public function __construct(private GameRepository $gameRepository, private EventBus $eventBus) {}

    public function __invoke(SetGameFinished $setGameFinished): void
    {
        $game = $this->gameRepository->find($setGameFinished->getGameId());

        // Reopening clears the whistle rather than remembering it: a game that
        // is running again has no end, and a later one gets its own timestamp.
        $game->setFinishedAt($setGameFinished->isFinished() ? new \DateTimeImmutable() : null);

        $this->gameRepository->save($game, true);

        $this->eventBus->dispatch(new GameStateChanged($game->getSlug()));
    }
}
