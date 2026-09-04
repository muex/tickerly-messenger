<?php

namespace App\Controller;

use App\Entity\Game;
use App\Form\GameEventType;
use App\Form\GameType;
use App\Game\Application\Command\CreateGame;
use App\Game\Application\Command\CreateGameEvent;
use App\Game\Application\Command\DecreaseAwayPoints;
use App\Game\Application\Command\DecreaseHomePoints;
use App\Game\Application\Command\DeleteGame;
use App\Game\Application\Command\IncreaseAwayPoints;
use App\Game\Application\Command\IncreaseHomePoints;
use App\Game\Application\Command\SetGameFinished;
use App\Game\Application\Command\UpdateGame;
use App\Form\Model\GameData;
use App\Shared\Domain\CommandBus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/games')]
class GameCommandController extends AbstractController
{
    #[Route('/new', name: 'app_game_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, CommandBus $commandBus): Response
    {
        $form = $this->createForm(GameType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();

            $gameCommand = new CreateGame(
                $data->home,
                $data->away,
                $data->location,
                $data->datetime,
                $this->getUser()->getId(),
            );
            $commandBus->dispatch($gameCommand);

            return $this->redirectToRoute('app_game_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('game/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{slug}/edit', name: 'app_game_edit', methods: ['GET', 'POST'])]
    #[IsGranted('GAME_EDIT', subject: 'game')]
    public function edit(Request $request, Game $game, CommandBus $commandBus): Response
    {
        // Bound to a copy, not to the game itself: otherwise handleRequest()
        // would already have applied the change and the command below would be
        // describing something that had happened without it.
        $form = $this->createForm(GameType::class, GameData::fromGame($game));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $updateGameCommand = new UpdateGame(
                $game->getId(),
                $data->home,
                $data->away,
                $data->location,
                $data->datetime,
            );
            $commandBus->dispatch($updateGameCommand);

            return $this->redirectToRoute('app_game_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('game/edit.html.twig', [
            'game' => $game,
            'form' => $form,
        ]);
    }

    #[Route('/{slug}/increasehome', name: 'app_game_increase_home', methods: ['POST'])]
    #[IsGranted('GAME_SCORE', subject: 'game')]
    #[IsCsrfTokenValid('score')]
    public function increaseHomePoints(Request $request, Game $game, CommandBus $commandBus): Response
    {
        $id = $game->getId();
        $increaseHomeCommand = new IncreaseHomePoints($id);
        $commandBus->dispatch($increaseHomeCommand);

        return $this->redirectToRoute('app_game_show', ['slug' => $game->getSlug()]);
    }

    #[Route('/{slug}/decreasehome', name: 'app_game_decrease_home', methods: ['POST'])]
    #[IsGranted('GAME_SCORE', subject: 'game')]
    #[IsCsrfTokenValid('score')]
    public function decreaseHomePoints(Request $request, Game $game, CommandBus $commandBus): Response
    {
        $id = $game->getId();
        $increaseHomeCommand = new DecreaseHomePoints($id);
        $commandBus->dispatch($increaseHomeCommand);

        return $this->redirectToRoute('app_game_show', ['slug' => $game->getSlug()]);
    }

    #[Route('/{slug}/increaseaway', name: 'app_game_increase_away', methods: ['POST'])]
    #[IsGranted('GAME_SCORE', subject: 'game')]
    #[IsCsrfTokenValid('score')]
    public function increaseAwayPoints(Request $request, Game $game, CommandBus $commandBus): Response
    {
        $id = $game->getId();
        $increaseAwayCommand = new IncreaseAwayPoints($id);
        $commandBus->dispatch($increaseAwayCommand);

        return $this->redirectToRoute('app_game_show', ['slug' => $game->getSlug()]);
    }

    #[Route('/{slug}/decreaseaway', name: 'app_game_decrease_away', methods: ['POST'])]
    #[IsGranted('GAME_SCORE', subject: 'game')]
    #[IsCsrfTokenValid('score')]
    public function decreaseAwayPoints(Request $request, Game $game, CommandBus $commandBus): Response
    {
        $decreaseAwayCommand = new DecreaseAwayPoints($game->getId());
        $commandBus->dispatch($decreaseAwayCommand);

        return $this->redirectToRoute('app_game_show', ['slug' => $game->getSlug()]);
    }

    /**
     * The final whistle, and taking it back. One route for both directions,
     * because the owner is looking at the state they are flipping.
     */
    #[Route('/{slug}/finish', name: 'app_game_finish', methods: ['POST'])]
    #[IsGranted('GAME_EDIT', subject: 'game')]
    #[IsCsrfTokenValid('finish')]
    public function finish(Game $game, CommandBus $commandBus): Response
    {
        $commandBus->dispatch(new SetGameFinished($game->getId(), !$game->isFinished()));

        // No flash: only the admin layout renders them, and the page the owner
        // lands on says plainly enough whether the game is over.
        return $this->redirectToRoute('app_game_show', ['slug' => $game->getSlug()]);
    }

    #[Route('/{slug}/delete', name: 'app_game_delete', methods: ['POST'])]
    #[IsGranted('GAME_EDIT', subject: 'game')]
    #[IsCsrfTokenValid('delete')]
    public function delete(Request $request, Game $game, CommandBus $commandBus): Response
    {
        $deleteGameCommand = new DeleteGame($game->getId());
        $commandBus->dispatch($deleteGameCommand);

        return $this->redirectToRoute('app_game_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{slug}/events', name: 'app_event_new', methods: ['POST'])]
    #[IsGranted('GAME_SCORE', subject: 'game')]
    public function gameEventNew(Request $request, Game $game, CommandBus $commandBus): Response
    {
        $form = $this->createForm(GameEventType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $gameevent = $form->getData();

            $gameEventCommand = new CreateGameEvent(
                $game->getId(),
                $gameevent->getTimecode(),
                $gameevent->getMessage(),
            );
            $commandBus->dispatch($gameEventCommand);

            return $this->redirectToRoute('app_game_show', ['slug' => $game->getSlug()]);
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
