<?php

namespace App\Game\Application\Event;

use App\Shared\Domain\Event;

/**
 * Raised on the event bus whenever a game's persisted state changes
 * (created, updated, deleted, or scored). Signals that the read models
 * served to the index page are stale and must be rebuilt.
 */
class GameStateChanged implements Event
{
}
