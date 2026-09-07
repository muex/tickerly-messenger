<?php

declare(strict_types=1);

namespace App\Game\Domain;

/**
 * Which of the two teams something is credited to.
 */
enum Side: string
{
    case Home = 'home';
    case Away = 'away';
}
