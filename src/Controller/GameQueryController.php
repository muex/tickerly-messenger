<?php

namespace App\Controller;

use App\Entity\Game;
use App\Game\Infrastructure\GameCardRenderer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/games')]
class GameQueryController extends AbstractController
{
    #[Route('', name: 'app_game_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('game/index.html.twig');
    }

    // A slug always carries at least one hyphen ("falcons-vs-sharks-2026-08-25"),
    // so this can never swallow /games/new regardless of route ordering.
    #[Route('/{slug<[a-z0-9]+(?:-[a-z0-9]+)+>}', name: 'app_game_show', methods: ['GET'])]
    public function show(Game $game, GameCardRenderer $cardRenderer): Response
    {
        $this->denyUnlessVisible($game);

        return $this->render('game/show.html.twig', [
            'game' => $game,
            // Null where the server cannot draw the card, so the page leaves the
            // image tags out rather than pointing a crawler at a broken URL.
            'card_version' => $cardRenderer->isAvailable() ? $cardRenderer->versionFor($game) : null,
        ]);
    }

    /**
     * The scoreboard image that link previews show. Rendered on demand and
     * cached; the version in the URL changes with the score, so both the CDN
     * and the unfurler see a new image instead of a stale one.
     */
    #[Route('/{slug<[a-z0-9]+(?:-[a-z0-9]+)+>}/card.png', name: 'app_game_card', methods: ['GET'])]
    public function card(Game $game, Request $request, GameCardRenderer $cardRenderer): Response
    {
        $this->denyUnlessVisible($game);

        if (!$cardRenderer->isAvailable()) {
            throw new NotFoundHttpException('Kein Vorschaubild verfügbar.');
        }

        $response = new Response();
        $response->setEtag($cardRenderer->versionFor($game));
        $response->setPublic();
        $response->setMaxAge(300);

        if ($response->isNotModified($request)) {
            return $response;
        }

        $response->setContent($cardRenderer->render($game));
        $response->headers->set('Content-Type', 'image/png');

        return $response;
    }

    /**
     * A deactivated game stays reachable for its owner and for admins, but is
     * gone for the public — image included.
     */
    private function denyUnlessVisible(Game $game): void
    {
        if (!$game->isActive() && !$this->isGranted('GAME_EDIT', $game) && !$this->isGranted('ROLE_ADMIN')) {
            throw new NotFoundHttpException('Spiel nicht gefunden.');
        }
    }
}
