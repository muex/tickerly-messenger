<?php

declare(strict_types=1);

namespace App\Game\Domain\Sport;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Every sport the app knows, found by the tag on the interface. "Offen" comes
 * first because it is the fallback and the default; the rest are alphabetical,
 * so the order of the select does not depend on how the container happened to
 * discover the classes.
 */
class SportCatalog
{
    /** @var array<string, Sport> */
    private array $sports;

    /**
     * @param iterable<Sport> $sports
     */
    public function __construct(#[AutowireIterator('app.sport')] iterable $sports)
    {
        $byKey = [];

        foreach ($sports as $sport) {
            $byKey[$sport->key()] = $sport;
        }

        uasort($byKey, static function (Sport $a, Sport $b): int {
            if (($a->key() === OpenSport::KEY) !== ($b->key() === OpenSport::KEY)) {
                return $a->key() === OpenSport::KEY ? -1 : 1;
            }

            return $a->label() <=> $b->label();
        });

        $this->sports = $byKey;
    }

    /**
     * @return array<string, Sport>
     */
    public function all(): array
    {
        return $this->sports;
    }

    /**
     * Never fails: a game whose stored key was retired still has to render, and
     * it renders as what it effectively is — a plain scoreboard.
     */
    public function get(?string $key): Sport
    {
        return $this->sports[$key ?? ''] ?? $this->sports[OpenSport::KEY];
    }

    public function eventFor(?string $sportKey, string $eventKey): ?SportEvent
    {
        foreach ($this->get($sportKey)->events() as $event) {
            if ($event->key === $eventKey) {
                return $event;
            }
        }

        return null;
    }

    /**
     * Labels to keys, the shape a ChoiceType wants.
     *
     * @return array<string, string>
     */
    public function choices(): array
    {
        $choices = [];

        foreach ($this->sports as $key => $sport) {
            $choices[$sport->icon() . ' ' . $sport->label()] = $key;
        }

        return $choices;
    }
}
