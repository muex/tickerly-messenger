<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\User;
use App\Game\Application\Command\SetGameActive;
use App\Shared\Domain\CommandBus;
use App\Repository\GameRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Administration area: overview of the platform plus activating and
 * deactivating users and games.
 */
#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('', name: 'app_admin_dashboard', methods: ['GET'])]
    public function dashboard(UserRepository $userRepository, GameRepository $gameRepository): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'user_stats' => $userRepository->countByStatus(),
            'game_stats' => $gameRepository->countByStatus(),
            'latest_games' => \array_slice($gameRepository->findAllForAdmin(), 0, 5),
        ]);
    }

    #[Route('/users', name: 'app_admin_users', methods: ['GET'])]
    public function users(UserRepository $userRepository): Response
    {
        return $this->render('admin/users.html.twig', [
            'users' => $userRepository->findAllForAdmin(),
        ]);
    }

    #[Route('/users/{id}/toggle', name: 'app_admin_user_toggle', methods: ['POST'])]
    #[IsCsrfTokenValid('admin_toggle')]
    public function toggleUser(User $user, UserRepository $userRepository): Response
    {
        // An admin locking themselves out would leave the area unreachable.
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Du kannst dein eigenes Konto nicht deaktivieren.');

            return $this->redirectToRoute('app_admin_users');
        }

        $user->setActive(!$user->isActive());
        $userRepository->save($user, true);

        $this->addFlash('success', sprintf(
            '%s wurde %s.',
            $user->getEmail(),
            $user->isActive() ? 'aktiviert' : 'deaktiviert',
        ));

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/games', name: 'app_admin_games', methods: ['GET'])]
    public function games(GameRepository $gameRepository): Response
    {
        return $this->render('admin/games.html.twig', [
            'games' => $gameRepository->findAllForAdmin(),
        ]);
    }

    #[Route('/games/{id}/toggle', name: 'app_admin_game_toggle', methods: ['POST'])]
    #[IsCsrfTokenValid('admin_toggle')]
    public function toggleGame(Game $game, CommandBus $commandBus): Response
    {
        $activate = !$game->isActive();
        $commandBus->dispatch(new SetGameActive($game->getId(), $activate));

        $this->addFlash('success', sprintf(
            '%s : %s wurde %s.',
            $game->getHome(),
            $game->getAway(),
            $activate ? 'aktiviert' : 'deaktiviert',
        ));

        return $this->redirectToRoute('app_admin_games');
    }
}
