<?php

declare(strict_types=1);

namespace App\Game\Domain\Sport;

/**
 * One thing that can happen in a sport, as the owner taps it: a label for the
 * button and the ticker, a symbol the entry is recognised by, and what it does
 * to the score. A red card moves nothing; a touchdown moves six.
 */
final class SportEvent
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $icon,
        public readonly int $points = 0,
    ) {}
}
