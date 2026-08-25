<?php

namespace App\Tests\Game\Infrastructure;

use App\Entity\Game;
use App\Game\Infrastructure\GameCardRenderer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GameCardRendererTest extends KernelTestCase
{
    public function testTheVersionFollowsTheScore(): void
    {
        $renderer = $this->renderer();
        $game = $this->game();

        $before = $renderer->versionFor($game);
        $game->setHomepoints(1);

        // The version is the cache key and the cache buster in the image URL:
        // if it did not move, unfurlers would keep showing the old score.
        $this->assertNotSame($before, $renderer->versionFor($game));
    }

    public function testItDrawsACardInTheSizeLinkPreviewsExpect(): void
    {
        $renderer = $this->renderer();

        if (!$renderer->isAvailable()) {
            $this->markTestSkipped('No GD with FreeType and a usable font on this machine.');
        }

        $size = getimagesizefromstring($renderer->render($this->game()));

        $this->assertSame('image/png', $size['mime']);
        $this->assertSame([1200, 630], [$size[0], $size[1]]);
    }

    private function renderer(): GameCardRenderer
    {
        self::bootKernel();

        return self::getContainer()->get(GameCardRenderer::class);
    }

    private function game(): Game
    {
        return (new Game())
            ->setHome('Falcons')
            ->setAway('Sharks')
            ->setLocation('Stadthalle')
            ->setDatetime(new \DateTime('2026-08-25 19:30'))
            ->setSlug('falcons-vs-sharks-2026-08-25')
            ->setHomepoints(0)
            ->setAwaypoints(0);
    }
}
