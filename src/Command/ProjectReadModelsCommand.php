<?php

declare(strict_types=1);

namespace App\Command;

use App\Game\Infrastructure\GameProjector;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Rebuilds the generated JSON read models in public/. The deploy excludes them
 * from rsync, so this is what puts them back afterwards — and what backfills
 * games that have not changed since the snapshots were introduced.
 */
#[AsCommand(
    name: 'app:project-read-models',
    description: 'Rebuild the JSON read models served to the browser',
)]
class ProjectReadModelsCommand extends Command
{
    public function __construct(private GameProjector $gameProjector)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->gameProjector->projectAll();

        (new SymfonyStyle($input, $output))->success('Read models rebuilt.');

        return Command::SUCCESS;
    }
}
