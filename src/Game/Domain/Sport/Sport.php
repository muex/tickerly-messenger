<?php

declare(strict_types=1);

namespace App\Game\Domain\Sport;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * A sport a game can be tickered in. Adding one is a single class in this
 * namespace — it is picked up by the tag and appears in the form, on the page
 * and in the snapshot without anything else being touched.
 */
#[AutoconfigureTag('app.sport')]
interface Sport
{
    /** Stored on the game, so it must not change once games use it. */
    public function key(): string;

    public function label(): string;

    public function icon(): string;

    /**
     * The buttons the owner gets, in the order they are shown.
     *
     * @return SportEvent[]
     */
    public function events(): array;
}
