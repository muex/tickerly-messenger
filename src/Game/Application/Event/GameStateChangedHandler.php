<?php

namespace App\Game\Application\Event;

use App\Game\Infrastructure\GameProjector;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
class GameStateChangedHandler
{
    public function __construct(private GameProjector $gameProjector) {}

    public function __invoke(GameStateChanged $event): void
    {
        $this->gameProjector->projectReadModels();
    }
}
