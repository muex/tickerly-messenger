<?php

declare(strict_types=1);

namespace App\Game\Domain;

use App\Repository\GameRepository;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Builds the public identifier of a game, e.g. "falcons-vs-sharks-2026-08-25".
 *
 * The kickoff date makes a repeat fixture unique on its own; a counter is only
 * appended when the same teams meet twice on the same day.
 */
class GameSlugger
{
    public function __construct(
        private SluggerInterface $slugger,
        private GameRepository $gameRepository,
    ) {}

    public function slugFor(string $home, string $away, \DateTimeInterface $kickoff): string
    {
        // German transliteration, so "Wölfe" becomes "woelfe" and not "wolfe".
        $base = $this->slugger
            ->slug(sprintf('%s vs %s %s', $home, $away, $kickoff->format('Y-m-d')), '-', 'de')
            ->lower()
            ->toString();

        $slug = $base;

        for ($suffix = 2; $this->gameRepository->findOneBy(['slug' => $slug]) !== null; ++$suffix) {
            $slug = $base . '-' . $suffix;
        }

        return $slug;
    }
}
