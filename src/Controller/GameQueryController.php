<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\GameEvent;
use App\Form\GameType;
use App\Form\GameEventType;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/')]
class GameQueryController extends AbstractController
{
    #[Route('/', name: 'app_game_index', methods: ['GET'])]
    public function index(GameRepository $gameRepository): Response
    {
        return $this->render('game/index.html.twig', [
            'nextgames' => $gameRepository->findNextGames(),
            'lastgames' => $gameRepository->findLastGames(),
        ]);
    }
}
