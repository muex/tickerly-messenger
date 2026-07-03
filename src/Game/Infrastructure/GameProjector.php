<?php

declare(strict_types=1);

namespace App\Game\Infrastructure;

use App\Entity\Game;
use App\Repository\GameRepository;

/**
 * Maintains the JSON read models consumed by the game index page
 * (nextgames.json / lastgames.json). Rebuild both whenever the write
 * side changes so the displayed scores and lists stay in sync.
 */
class GameProjector
{
    public function __construct(private GameRepository $gameRepository) {}

    public function projectReadModels(): void
    {
        file_put_contents('nextgames.json', json_encode($this->toReadModel($this->gameRepository->findNextGames())));
        file_put_contents('lastgames.json', json_encode($this->toReadModel($this->gameRepository->findLastGames())));
    }

    /**
     * @param Game[] $games
     * @return array<int, array<string, mixed>>
     */
    private function toReadModel(array $games): array
    {
        $readModel = [];

        foreach ($games as $game) {
            $readModel[] = [
                'id' => $game->getId(),
                'home' => $game->getHome(),
                'away' => $game->getAway(),
                'location' => $game->getLocation(),
                'datetime' => $game->getDatetime(),
                'homepoints' => $game->getHomepoints(),
                'awaypoints' => $game->getAwaypoints(),
            ];
        }

        return $readModel;
    }
}
