<?php

declare(strict_types=1);

namespace App\Game\Infrastructure;

use App\Entity\Game;
use App\Repository\GameRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Maintains the JSON read models consumed by the browser: the two lists behind
 * the game index (nextgames.json / lastgames.json) and one snapshot per public
 * game (games/<slug>.json), which the game page polls for the live score.
 *
 * The files are written into the public/ web root so they can be fetched
 * directly by the browser, regardless of the process working directory (which
 * differs between the web front controller and an async messenger worker).
 * Serving a spectator from a static file is the point: a full ticker audience
 * reloading once a minute would otherwise be a PHP request each.
 */
class GameProjector
{
    private const GAME_DIRECTORY = '/games';

    public function __construct(
        private GameRepository $gameRepository,
        #[Autowire('%kernel.project_dir%/public')] private string $webRoot,
    ) {}

    /**
     * @param string|null $slug Rebuild this game's own snapshot as well. Null
     *                          where the change touched no single game.
     */
    public function projectReadModels(?string $slug = null): void
    {
        $this->writeJson('/nextgames.json', $this->toReadModel($this->gameRepository->findNextGames()));
        $this->writeJson('/lastgames.json', $this->toReadModel($this->gameRepository->findLastGames()));

        if ($slug !== null) {
            $this->projectGame($slug);
        }
    }

    /**
     * Rebuilds every read model from scratch and drops snapshots that no longer
     * belong to a public game. Deploys wipe the generated files, and a game that
     * changed while they were gone would otherwise stay stale forever.
     */
    public function projectAll(): void
    {
        $this->projectReadModels();

        $current = [];

        foreach ($this->gameRepository->findActive() as $game) {
            $this->writeGame($game);
            $current[$game->getSlug() . '.json'] = true;
        }

        foreach (glob($this->webRoot . self::GAME_DIRECTORY . '/*.json') ?: [] as $file) {
            if (!isset($current[basename($file)])) {
                unlink($file);
            }
        }
    }

    /**
     * A snapshot lives in the web root, where the web server hands it out before
     * any voter runs. A game that stopped being public therefore has to lose its
     * file, not just its place in the lists.
     */
    private function projectGame(string $slug): void
    {
        $game = $this->gameRepository->findOneBy(['slug' => $slug]);

        if ($game === null || !$game->isActive()) {
            $file = $this->pathFor($slug);

            if ($file !== null && is_file($file)) {
                unlink($file);
            }

            return;
        }

        $this->writeGame($game);
    }

    private function writeGame(Game $game): void
    {
        $slug = $game->getSlug();

        if ($this->pathFor($slug) === null) {
            return;
        }

        $this->writeJson(self::GAME_DIRECTORY . '/' . $slug . '.json', [
            'slug' => $slug,
            'home' => $game->getHome(),
            'away' => $game->getAway(),
            'homepoints' => $game->getHomepoints(),
            'awaypoints' => $game->getAwaypoints(),
            'events' => $this->toEventReadModel($game),
        ]);
    }

    /**
     * Slugs are generated, never free text — but they end up in a filesystem
     * path here, so anything that is not one is refused rather than trusted.
     */
    private function pathFor(string $slug): ?string
    {
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)+$/', $slug) !== 1) {
            return null;
        }

        return $this->webRoot . self::GAME_DIRECTORY . '/' . $slug . '.json';
    }

    /**
     * Write the file atomically: a browser fetching the JSON never observes a
     * half-written file, since the rename is atomic on the same filesystem.
     *
     * @param array<array-key, mixed> $data
     */
    private function writeJson(string $filename, array $data): void
    {
        $target = $this->webRoot . $filename;
        $directory = \dirname($target);

        if (!is_dir($directory)) {
            mkdir($directory, 0o775, true);
        }

        $tmp = $target . '.' . bin2hex(random_bytes(4)) . '.tmp';

        file_put_contents($tmp, json_encode($data));
        rename($tmp, $target);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function toEventReadModel(Game $game): array
    {
        $events = [];

        foreach ($game->getGameEvents() as $event) {
            $events[] = [
                'timecode' => $event->getTimecode(),
                'message' => $event->getMessage(),
            ];
        }

        return $events;
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
