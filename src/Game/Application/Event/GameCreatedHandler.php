<?php

namespace App\Game\Application\Event;

use App\Game\Infrastructure\GameProjector;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class GameCreatedHandler implements MessageHandlerInterface
{
    public function __construct(private GameProjector $gameProjector) {}

    public function __invoke(GameCreated $event): void
    {
        $this->gameProjector->projectReadModels();
    }
}
