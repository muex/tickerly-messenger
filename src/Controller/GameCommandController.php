<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\GameEvent;
use App\Form\GameEventType;
use App\Form\GameType;
use App\Game\Application\Command\CreateGame;
use App\Game\Application\Command\DecreaseHomePoints;
use App\Game\Application\Command\IncreaseHomePoints;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class GameCommandController extends AbstractController
{
    #[Route('/new', name: 'app_game_new', methods: ['GET', 'POST'])]
    public function new(Request $request, MessageBusInterface $commandBus): Response
    {
        $form = $this->createForm(GameType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $game = $form->getData();

            $gameCommand = new CreateGame(
                $game->getHome(),
                $game->getAway(),
                $game->getPlace(),
                $game->getDatetime(),
                $this->getUser()
            );
            $commandBus->dispatch($gameCommand);

            return $this->redirectToRoute('app_game_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('game/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_game_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Game $game, GameRepository $gameRepository): Response
    {
        $form = $this->createForm(GameType::class, $game);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $gameRepository->save($game, true);

            return $this->redirectToRoute('app_game_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('game/edit.html.twig', [
            'game' => $game,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/increasehome', name: 'app_game_increase_home', methods: ['GET', 'POST'])]
    public function increaseHomePoints(Request $request, Game $game, MessageBusInterface $commandBus): Response
    {
        $id = $game->getId();
        $increaseHomeCommand = new IncreaseHomePoints($id);
        $commandBus->dispatch($increaseHomeCommand);

        return $this->redirectToRoute('app_game_show', ['id' => $game->getId()]);
    }

    #[Route('/{id}/decreasehome', name: 'app_game_decrease_home', methods: ['GET', 'POST'])]
    public function decreaseHomePoints(Request $request, Game $game, MessageBusInterface $commandBus): Response
    {
        $id = $game->getId();
        $increaseHomeCommand = new DecreaseHomePoints(
            $id
        );
        $commandBus->dispatch($increaseHomeCommand);

        return $this->redirectToRoute('app_game_show', ['id' => $game->getId()]);
    }

    #[Route('/{id}/increaseaway', name: 'app_game_increase_away', methods: ['GET', 'POST'])]
    public function increaseAwayPoints(Request $request, Game $game, GameRepository $gameRepository, EntityManagerInterface $entityManager): Response
    {
        $awayPoints = $game->getAwaypoints();
        $game->setAwaypoints(++$awayPoints);

        $entityManager->persist($game);
        $entityManager->flush();

        return $this->redirectToRoute('app_game_show', ['id' => $game->getId()]);
    }

    #[Route('/{id}/decreaseaway', name: 'app_game_decrease_away', methods: ['GET', 'POST'])]
    public function decreaseAwayPoints(Request $request, Game $game, GameRepository $gameRepository, EntityManagerInterface $entityManager): Response
    {
        $awayPoints = $game->getAwaypoints();
        $game->setAwaypoints(--$awayPoints);

        $entityManager->persist($game);
        $entityManager->flush();

        return $this->redirectToRoute('app_game_show', ['id' => $game->getId()]);
    }

    #[Route('/delete/{id}', name: 'app_game_delete', methods: ['POST'])]
    public function delete(Request $request, Game $game, GameRepository $gameRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$game->getId(), $request->request->get('_token'))) {
            $gameRepository->remove($game, true);
        }

        return $this->redirectToRoute('app_game_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{game_id}/newevent', name: 'app_event_new', methods: ['POST'])]
    #[ParamConverter('game', options: ['mapping' => ['game_id' => 'id']])]
    public function gameEventNew(Request $request, Game $game, EventDispatcherInterface $eventDispatcher, EntityManagerInterface $entityManager): Response
    {
        $gameEvent = new GameEvent();
        $game->addGameEvent($gameEvent);

        $form = $this->createForm(GameEventType::class, $gameEvent);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($gameEvent);
            $entityManager->flush();

            return $this->redirectToRoute('app_game_show', ['id' => $game->getId()]);
        }

        return $this->render('game/gameevent_form_error.html.twig', [
            'game' => $game,
            'form' => $form->createView(),
        ]);
    }

    public function gameEventForm(Game $game): Response
    {
        $form = $this->createForm(GameEventType::class);
        return $this->render('game/_game_event_form.html.twig', [
            'game' => $game,
            'form' => $form->createView(),
        ]);
    }
}
