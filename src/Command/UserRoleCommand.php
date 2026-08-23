<?php

namespace App\Command;

use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Grants or revokes ROLE_ADMIN. Needed at least once to bootstrap the very
 * first admin, since the admin area itself requires that role.
 */
#[AsCommand(name: 'app:user:promote', description: 'Grant or revoke ROLE_ADMIN for a user')]
class UserRoleCommand extends Command
{
    public function __construct(private UserRepository $userRepository)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'E-mail address of the user')
            ->addOption('demote', null, InputOption::VALUE_NONE, 'Revoke ROLE_ADMIN instead of granting it')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (null === $user) {
            $io->error(sprintf('No user found for "%s".', $email));

            return Command::FAILURE;
        }

        $roles = array_values(array_diff($user->getRoles(), ['ROLE_ADMIN', 'ROLE_USER']));

        if (!$input->getOption('demote')) {
            $roles[] = 'ROLE_ADMIN';
        }

        $user->setRoles($roles);
        $this->userRepository->save($user, true);

        $io->success(sprintf(
            '%s is now %s.',
            $email,
            $input->getOption('demote') ? 'a regular user' : 'an admin',
        ));

        return Command::SUCCESS;
    }
}
