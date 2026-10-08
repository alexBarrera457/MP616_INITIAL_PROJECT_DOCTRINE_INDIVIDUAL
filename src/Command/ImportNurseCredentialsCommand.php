<?php

namespace App\Command;

use App\Service\NurseCredentialImporter;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:nurse-credentials:import',
    description: 'Import validated nurse credentials from a JSON file'
)]
final class ImportNurseCredentialsCommand extends Command
{
    public function __construct(private readonly NurseCredentialImporter $importer)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('file', InputArgument::REQUIRED, 'Path to the nurse credentials JSON file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $count = $this->importer->importFile((string) $input->getArgument('file'));
        } catch (InvalidArgumentException $exception) {
            $output->writeln('<error>'.$exception->getMessage().'</error>');

            return Command::FAILURE;
        }

        $output->writeln(sprintf('<info>Imported %d nurse credential(s).</info>', $count));

        return Command::SUCCESS;
    }
}
