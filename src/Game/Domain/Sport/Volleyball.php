<?php

declare(strict_types=1);

namespace App\Game\Domain\Sport;

/**
 * Counts points, not sets: the scoreboard is one pair of numbers, so a set win
 * is an entry in the ticker like anything else.
 */
final class Volleyball implements Sport
{
    public function key(): string
    {
        return 'volleyball';
    }

    public function label(): string
    {
        return 'Volleyball';
    }

    public function icon(): string
    {
        return '🏐';
    }

    public function events(): array
    {
        return [
            new SportEvent('punkt', 'Punkt', '🏐', 1),
            new SportEvent('ass', 'Ass', '🎯', 1),
            new SportEvent('block', 'Block', '🧱', 1),
            new SportEvent('satzgewinn', 'Satzgewinn', '🏆'),
            new SportEvent('auszeit', 'Auszeit', '⏱'),
        ];
    }
}
