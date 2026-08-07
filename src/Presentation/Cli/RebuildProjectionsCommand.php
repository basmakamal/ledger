<?php

declare(strict_types=1);

namespace Ledger\Presentation\Cli;

use Ledger\Application\Projection\RebuildProjections;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'ledger:projections:rebuild',
    description: 'Drop every read model and rebuild it from the event stream',
)]
final class RebuildProjectionsCommand extends Command
{
    public function __construct(private readonly RebuildProjections $rebuild)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $replayed = ($this->rebuild)();

        $io->success(sprintf('Replayed %d events.', $replayed));

        return Command::SUCCESS;
    }
}
