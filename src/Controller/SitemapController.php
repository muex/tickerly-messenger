<?php

namespace App\Controller;

use App\Repository\GameRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapController extends AbstractController
{
    /**
     * Lists what a crawler should know about: the two landing pages and every
     * game that is currently public. Deactivated games are left out — they
     * answer 404 to anyone but their owner.
     */
    #[Route('/sitemap.xml', name: 'app_sitemap', defaults: ['_format' => 'xml'], methods: ['GET'])]
    public function sitemap(GameRepository $gameRepository): Response
    {
        $urls = [
            $this->generateUrl('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL),
            $this->generateUrl('app_game_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        foreach ([...$gameRepository->findNextGames(), ...$gameRepository->findLastGames()] as $game) {
            $urls[] = $this->generateUrl('app_game_show', ['slug' => $game->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        $response = $this->render('sitemap.xml.twig', ['urls' => $urls]);
        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');
        $response->setPublic();
        $response->setMaxAge(3600);

        return $response;
    }
}
