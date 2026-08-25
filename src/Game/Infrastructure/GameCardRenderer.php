<?php

declare(strict_types=1);

namespace App\Game\Infrastructure;

use App\Entity\Game;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Draws the scoreboard card that link previews show for a game.
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
     * no usable font. The game page then simply omits the image tags instead of
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

        $png = $this->draw($game);

        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0777, true);
        }

        // Same atomic write as the JSON read models: never serve a half file.
        $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (@file_put_contents($tmp, $png) !== false) {
            @rename($tmp, $file);
        }

        return $png;
    }

    private function draw(Game $game): string
    {
        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);

        $background = imagecolorallocate($image, 0xF5, 0xF5, 0xF7);
        $ink900 = imagecolorallocate($image, 0x1D, 0x1D, 0x1F);
        $ink500 = imagecolorallocate($image, 0x6E, 0x6E, 0x73);
        $ink400 = imagecolorallocate($image, 0x86, 0x86, 0x8B);
        $brand = imagecolorallocate($image, 0x00, 0x71, 0xE3);
        $white = imagecolorallocate($image, 0xFF, 0xFF, 0xFF);

        imagefilledrectangle($image, 0, 0, self::WIDTH, self::HEIGHT, $background);
        // The card the app itself uses, inset with a soft edge.
        imagefilledrectangle($image, 64, 64, self::WIDTH - 64, self::HEIGHT - 64, $white);
        imagefilledrectangle($image, 64, self::HEIGHT - 72, self::WIDTH - 64, self::HEIGHT - 64, $brand);

        $regular = $this->font('regular');
        $bold = $this->font('bold');
        $inner = self::WIDTH - 2 * 140;

        // Wordmark, top left inside the card.
        imagefilledellipse($image, 132, 136, 22, 22, $ink900);
        imagettftext($image, 26, 0, 156, 145, $ink500, $bold, 'tickerly');

        // The pairing goes on one line where it fits. Long club names would
        // otherwise shrink below the meta line and invert the hierarchy, so they
        // stack instead — home on top, matching the score reading left to right.
        $pairing = sprintf('%s : %s', $game->getHome(), $game->getAway());

        if ($this->widthOf($pairing, $bold, 46) <= $inner) {
            $this->centered($image, $pairing, $bold, 46, 300, $ink500, $inner, 46);
        } else {
            $this->centered($image, (string) $game->getHome(), $bold, 38, 250, $ink500, $inner, 28);
            $this->centered($image, (string) $game->getAway(), $bold, 38, 305, $ink500, $inner, 28);
        }

        $this->centered($image, sprintf('%d : %d', $game->getHomepoints() ?? 0, $game->getAwaypoints() ?? 0), $bold, 150, 460, $ink900, $inner, 150);

        $meta = array_filter([
            $game->getLocation(),
            $game->getDatetime()?->format('d.m.Y H:i'),
        ]);
        $this->centered($image, implode('  ·  ', $meta), $regular, 30, 525, $ink400, $inner, 22);

        ob_start();
        imagepng($image, null, 6);
        // No imagedestroy(): the handle is freed with the object, and the call
        // is deprecated as of PHP 8.5.
        return ob_get_clean();
    }

    /**
     * Draws one line centred, shrinking it until it fits and, only if that is
     * not enough, cutting it short — a long club name must not run off the card.
     */
    private function centered($image, string $text, string $font, float $size, int $baseline, int $color, int $maxWidth, float $minSize): void
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
