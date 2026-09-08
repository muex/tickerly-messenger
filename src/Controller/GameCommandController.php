<?php

namespace App\Controller;

use App\Entity\Game;
use App\Form\GameType;
use App\Game\Application\Command\CreateGame;
use App\Game\Application\Command\CreateGameEvent;
use App\Game\Application\Command\DecreaseAwayPoints;
use App\Game\Application\Command\DecreaseHomePoints;
use App\Game\Application\Command\DeleteGame;
use App\Game\Application\Command\IncreaseAwayPoints;
use App\Game\Application\Command\IncreaseHomePoints;
use App\Game\Application\Command\RecordSportEvent;
use App\Game\Application\Command\SetGameFinished;
use App\Game\Application\Command\UpdateGame;
use App\Form\Model\GameData;
use App\Game\Domain\Side;
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
                $data->sport,
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
                $data->sport,
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
    /**
     * The one row the owner types into: minute, what happened, a note. Picking
     * an event records it with its points; picking nothing records the note on
     * its own. The handler refuses anything that is not an event of this game's
     * sport, so the points a request can award are not the caller's to choose.
     */
    #[Route('/{slug}/record', name: 'app_game_record', methods: ['POST'])]
    #[IsGranted('GAME_SCORE', subject: 'game')]
    #[IsCsrfTokenValid('score')]
    public function record(Request $request, Game $game, CommandBus $commandBus): Response
    {
        $timecode = $this->trimmed($request, 'timecode');
        $note = $this->trimmed($request, 'note');

        // "tor:home" — one value carries what happened and which team it
        // belongs to, so the row stays three fields wide.
        [$eventKey, $sideKey] = array_pad(explode(':', (string) $request->request->get('event'), 2), 2, '');
        $side = Side::tryFrom($sideKey);

        if ($eventKey !== '' && $side !== null) {
            $commandBus->dispatch(new RecordSportEvent($game->getId(), $eventKey, $side, $timecode, $note));
        } elseif ($note !== null) {
            $commandBus->dispatch(new CreateGameEvent($game->getId(), $timecode, $note));
        }

        // Nothing chosen and nothing typed: nothing happened. The page comes
        // back as it was rather than complaining about an empty form.
        return $this->redirectToRoute('app_game_show', ['slug' => $game->getSlug()]);
    }

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

    private function trimmed(Request $request, string $field): ?string
    {
        $value = trim((string) $request->request->get($field));

        return $value === '' ? null : $value;
    }
}
