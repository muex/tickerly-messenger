<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\Game;

/**
 * What the game form edits.
 *
 * Deliberately not the Game entity: bound to a managed one, the form would
 * apply the change itself while handling the request, and the command that
 * follows would only be describing something that had already happened. With a
 * plain object in between, the handler stays the only writer.
 */
class GameData
{
    public ?string $home = null;

    public ?string $away = null;

    public ?string $location = null;

    public ?\DateTimeInterface $datetime = null;

    public static function fromGame(Game $game): self
    {
        $data = new self();
        $data->home = $game->getHome();
        $data->away = $game->getAway();
        $data->location = $game->getLocation();
        $data->datetime = $game->getDatetime();

        return $data;
    }
}
