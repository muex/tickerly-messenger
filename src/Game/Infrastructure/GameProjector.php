<?php

declare(strict_types=1);

namespace App\Game\Infrastructure;

use App\Entity\Game;
use App\Repository\GameRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Maintains the JSON read models consumed by the game index page
 * (nextgames.json / lastgames.json). Rebuild both whenever the write
 * side changes so the displayed scores and lists stay in sync.
 *
 * The files are written into the public/ web root so they can be
 * fetched directly by the browser, regardless of the process working
 * directory (which differs between the web front controller and an
 * async messenger worker).
 */
class GameProjector
{
    public function __construct(
        private GameRepository $gameRepository,
        #[Autowire('%kernel.project_dir%/public')] private string $webRoot,
    ) {}

    public function projectReadModels(): void
    {
        $this->writeJson('/nextgames.json', $this->toReadModel($this->gameRepository->findNextGames()));
        $this->writeJson('/lastgames.json', $this->toReadModel($this->gameRepository->findLastGames()));
    }

    /**
     * Write the file atomically: a browser fetching the JSON never observes a
     * half-written file, since the rename is atomic on the same filesystem.
     *
     * @param array<int, array<string, mixed>> $data
     */
    private function writeJson(string $filename, array $data): void
    {
        $target = $this->webRoot . $filename;
        $tmp = $target . '.' . bin2hex(random_bytes(4)) . '.tmp';

        file_put_contents($tmp, json_encode($data));
        rename($tmp, $target);
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
                'id' => (string) $game->getId(),
                'slug' => $game->getSlug(),
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
