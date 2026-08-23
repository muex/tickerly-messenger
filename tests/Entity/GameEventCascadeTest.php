<?php

namespace App\Tests\Entity;

use App\Entity\Game;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GameEventCascadeTest extends KernelTestCase
{
    /**
     * Without this, deleting a game that already has ticker events dies on the
     * game_event foreign key with a 1451 integrity constraint violation.
     */
    public function testRemovingAGameTakesItsEventsWithIt(): void
    {
        self::bootKernel();

        $mapping = self::getContainer()->get(EntityManagerInterface::class)
            ->getClassMetadata(Game::class)
            ->getAssociationMapping('gameEvents');

        $this->assertTrue($mapping['orphanRemoval']);
        $this->assertContains('remove', $mapping['cascade']);
    }
}
