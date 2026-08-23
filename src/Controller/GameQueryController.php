<?php

namespace App\Controller;

use App\Entity\Game;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function show(Game $game): Response
    {
        // A deactivated game stays reachable for its owner and for admins,
        // but is gone for the public.
        if (!$game->isActive() && !$this->isGranted('GAME_EDIT', $game) && !$this->isGranted('ROLE_ADMIN')) {
            throw new NotFoundHttpException('Spiel nicht gefunden.');
        }

        return $this->render('game/show.html.twig', [
            'game' => $game,
        ]);
    }
}
