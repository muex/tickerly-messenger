<?php

namespace App\Controller;

use App\Game\Domain\Sport\OpenSport;
use App\Game\Domain\Sport\SportCatalog;
use App\Game\Infrastructure\GameCardRenderer;
use App\Repository\GameRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(
        GameRepository $gameRepository,
        GameCardRenderer $cardRenderer,
        SportCatalog $sportCatalog,
    ): Response {
        // Rendered server-side on purpose: the ticker list on /games is built by
        // JavaScript from the JSON read models, so without these the landing
        // page offers a crawler no way into the actual game pages.
        $upcoming = \array_slice($gameRepository->findNextGames(), 0, 4);
        $recent = \array_slice($gameRepository->findLastGames(), 0, 4);

        // Named on the page because that is what people search for — and taken
        // from the catalog, so a new sport advertises itself.
        $sports = array_filter(
            $sportCatalog->all(),
            static fn (string $key): bool => $key !== OpenSport::KEY,
            ARRAY_FILTER_USE_KEY,
        );

        return $this->render('home/index.html.twig', [
            'upcoming_games' => $upcoming,
            'recent_games' => $recent,
            'sports' => $sports,
            'site_card' => $cardRenderer->isAvailable(),
        ]);
    }

    /**
     * The preview image for the site itself, for links that point at the
     * landing page rather than at one game.
     */
    #[Route('/card.png', name: 'app_site_card', methods: ['GET'])]
    public function siteCard(GameCardRenderer $cardRenderer): Response
    {
        if (!$cardRenderer->isAvailable()) {
            throw $this->createNotFoundException('Kein Vorschaubild verfügbar.');
        }

        $response = new Response($cardRenderer->renderSiteCard(), Response::HTTP_OK, ['Content-Type' => 'image/png']);
        $response->setPublic();
        $response->setMaxAge(86400);

        return $response;
    }
}
