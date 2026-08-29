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
    /**
     * @param string|null $slug The game whose own snapshot went stale with it.
     *                          Null where no single game is meant and only the
     *                          shared lists need rebuilding.
     */
    public function __construct(private ?string $slug = null) {}

    public function getSlug(): ?string
    {
        return $this->slug;
    }
}
