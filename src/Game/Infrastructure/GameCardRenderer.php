<?php

declare(strict_types=1);

namespace App\Game\Infrastructure;

use App\Entity\Game;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Draws the cards that link previews show: the scoreboard for a single game,
 * and the plain one for the site itself.
 *
 * Rendering is on demand rather than on every score change: an unfurler asks
 * for the image far less often than the ticker is tapped. The result is cached
 * under a key that contains the score, so a new score means a new file and a
 * new URL, and a stale card is never served.
 */
class GameCardRenderer
{
    private const WIDTH = 1200;
    private const HEIGHT = 630;

    public function __construct(
        #[Autowire('%app.game_card.fonts%')] private array $fontCandidates,
        #[Autowire('%kernel.cache_dir%/game_cards')] private string $cacheDir,
    ) {}

    /**
     * False when the server cannot draw the card at all — no GD, no FreeType or
     * no usable font. The pages then simply omit the image tags instead of
     * pointing a crawler at a broken URL.
     */
    public function isAvailable(): bool
    {
        return \function_exists('imagettftext')
            && $this->font('regular') !== null
            && $this->font('bold') !== null;
    }

    /**
     * Changes whenever anything drawn on the card changes, which makes it both
     * the cache key and the cache buster in the image URL.
     */
    public function versionFor(Game $game): string
    {
        return substr(hash('xxh128', implode('|', [
            $game->getHome(),
            $game->getAway(),
            $game->getHomepoints(),
            $game->getAwaypoints(),
            $game->getLocation(),
            $game->getDatetime()?->format('c'),
        ])), 0, 12);
    }

    public function render(Game $game): string
    {
        $file = sprintf('%s/%s-%s.png', $this->cacheDir, $game->getSlug(), $this->versionFor($game));

        if (is_file($file)) {
            return file_get_contents($file);
        }

        [$image, $colors] = $this->frame();
        $bold = $this->font('bold');
        $regular = $this->font('regular');
        $inner = self::WIDTH - 2 * 140;

        // The pairing goes on one line where it fits. Long club names would
        // otherwise shrink below the meta line and invert the hierarchy, so they
        // stack instead — home on top, matching the score reading left to right.
        $pairing = sprintf('%s : %s', $game->getHome(), $game->getAway());

        if ($this->widthOf($pairing, $bold, 46) <= $inner) {
            $this->centered($image, $pairing, $bold, 46, 300, $colors['ink500'], $inner, 46);
        } else {
            $this->centered($image, (string) $game->getHome(), $bold, 38, 250, $colors['ink500'], $inner, 28);
            $this->centered($image, (string) $game->getAway(), $bold, 38, 305, $colors['ink500'], $inner, 28);
        }

        $this->centered($image, sprintf('%d : %d', $game->getHomepoints(), $game->getAwaypoints()), $bold, 150, 460, $colors['ink900'], $inner, 150);

        $meta = array_filter([
            $game->getLocation(),
            $game->getDatetime()?->format('d.m.Y H:i'),
        ]);
        $this->centered($image, implode('  ·  ', $meta), $regular, 30, 525, $colors['ink400'], $inner, 22);

        return $this->store($file, $this->toPng($image));
    }

    /**
     * The card for the site itself, shown when the landing page is shared.
     * Same frame as a game card, only without a score to put in it.
     */
    public function renderSiteCard(): string
    {
        $file = $this->cacheDir . '/site.png';

        if (is_file($file)) {
            return file_get_contents($file);
        }

        // No small wordmark here: on this card the big one *is* the wordmark.
        [$image, $colors] = $this->frame(withWordmark: false);
        $inner = self::WIDTH - 2 * 140;

        $this->centered($image, 'tickerly', $this->font('bold'), 120, 330, $colors['ink900'], $inner, 120);
        $this->centered($image, 'Live-Ticker für jedes Spiel. In Echtzeit.', $this->font('regular'), 40, 410, $colors['ink500'], $inner, 26);
        $this->centered($image, 'tickerly.de', $this->font('regular'), 28, 500, $colors['ink400'], $inner, 22);

        return $this->store($file, $this->toPng($image));
    }

    /**
     * The empty card every preview image starts from: the app's own background,
     * the white card inset, the brand bar and the wordmark.
     *
     * @return array{\GdImage, array<string, int>}
     */
    private function frame(bool $withWordmark = true): array
    {
        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);

        $colors = [
            'background' => imagecolorallocate($image, 0xF5, 0xF5, 0xF7),
            'ink900' => imagecolorallocate($image, 0x1D, 0x1D, 0x1F),
            'ink500' => imagecolorallocate($image, 0x6E, 0x6E, 0x73),
            'ink400' => imagecolorallocate($image, 0x86, 0x86, 0x8B),
            'brand' => imagecolorallocate($image, 0x00, 0x71, 0xE3),
            'white' => imagecolorallocate($image, 0xFF, 0xFF, 0xFF),
        ];

        imagefilledrectangle($image, 0, 0, self::WIDTH, self::HEIGHT, $colors['background']);
        imagefilledrectangle($image, 64, 64, self::WIDTH - 64, self::HEIGHT - 64, $colors['white']);
        imagefilledrectangle($image, 64, self::HEIGHT - 72, self::WIDTH - 64, self::HEIGHT - 64, $colors['brand']);

        if ($withWordmark) {
            // Top left inside the card.
            imagefilledellipse($image, 132, 136, 22, 22, $colors['ink900']);
            imagettftext($image, 26, 0, 156, 145, $colors['ink500'], $this->font('bold'), 'tickerly');
        }

        return [$image, $colors];
    }

    private function toPng(\GdImage $image): string
    {
        ob_start();
        imagepng($image, null, 6);

        // No imagedestroy(): the handle is freed with the object, and the call
        // is deprecated as of PHP 8.5.
        return ob_get_clean();
    }

    /**
     * Write the file atomically, same as the JSON read models: a request that
     * arrives mid-render never sees half a picture.
     */
    private function store(string $file, string $png): string
    {
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0777, true);
        }

        $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (@file_put_contents($tmp, $png) !== false) {
            @rename($tmp, $file);
        }

        return $png;
    }

    /**
     * Draws one line centred, shrinking it until it fits and, only if that is
     * not enough, cutting it short — a long club name must not run off the card.
     */
    private function centered(\GdImage $image, string $text, string $font, float $size, int $baseline, int $color, int $maxWidth, float $minSize): void
    {
        while ($size > $minSize && $this->widthOf($text, $font, $size) > $maxWidth) {
            $size -= 2;
        }

        while ($text !== '' && $this->widthOf($text, $font, $size) > $maxWidth) {
            $text = mb_substr($text, 0, mb_strlen($text) - 2) . '…';
        }

        $x = (int) ((self::WIDTH - $this->widthOf($text, $font, $size)) / 2);
        imagettftext($image, $size, 0, $x, $baseline, $color, $font, $text);
    }

    private function widthOf(string $text, string $font, float $size): int
    {
        $box = imagettfbbox($size, 0, $font, $text);

        return (int) ($box[2] - $box[0]);
    }

    private function font(string $weight): ?string
    {
        foreach ($this->fontCandidates[$weight] ?? [] as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
