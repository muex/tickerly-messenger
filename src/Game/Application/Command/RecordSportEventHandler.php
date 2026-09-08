<?php

namespace App\Game\Application\Command;

use App\Entity\Game;
use App\Entity\GameEvent;
use App\Game\Application\Event\GameStateChanged;
use App\Game\Domain\Side;
use App\Game\Domain\Sport\SportCatalog;
use App\Game\Domain\Sport\SportEvent;
use App\Repository\GameRepository;
use App\Shared\Domain\EventBus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class RecordSportEventHandler
{
    public function __construct(
        private GameRepository $gameRepository,
        private SportCatalog $sportCatalog,
        private EventBus $eventBus,
    ) {}

    public function __invoke(RecordSportEvent $command): void
    {
        $game = $this->gameRepository->find($command->getGameId());

        if ($game === null) {
            throw new \RuntimeException('Cannot record an event on a game that no longer exists.');
        }

        $sportEvent = $this->sportCatalog->eventFor($game->getSport(), $command->getEventKey());

        // The button came from this game's sport or it did not happen. Without
        // this, a hand-written POST could invent an event worth any number of
        // points it liked.
        if ($sportEvent === null) {
            throw new \RuntimeException(sprintf(
                'Unknown event "%s" for sport "%s".',
                $command->getEventKey(),
                $game->getSport(),
            ));
        }

        $this->addPoints($game, $command->getSide(), $sportEvent->points);

        $entry = (new GameEvent())
            ->setType($sportEvent->key)
            ->setTimecode($command->getTimecode())
            ->setMessage($this->messageFor($game, $command->getSide(), $sportEvent, $command->getNote()));

        // The association cascades persist, so saving the game saves the entry.
        $game->addGameEvent($entry);
        $this->gameRepository->save($game, true);

        $this->eventBus->dispatch(new GameStateChanged($game->getSlug()));
    }

    private function addPoints(Game $game, Side $side, int $points): void
    {
        if ($points === 0) {
            return;
        }

        if ($side === Side::Home) {
            $game->setHomepoints($game->getHomepoints() + $points);

            return;
        }

        $game->setAwaypoints($game->getAwaypoints() + $points);
    }

    private function messageFor(Game $game, Side $side, SportEvent $sportEvent, ?string $note): string
    {
        $team = $side === Side::Home ? $game->getHome() : $game->getAway();
        $message = sprintf('%s für %s', $sportEvent->label, $team);

        // "Tor für Falcons — Nr. 8, aus 20 Metern": the event says what it was
        // worth, the note says what made it worth watching.
        return $note === null ? $message : $message . ' — ' . $note;
    }
}
