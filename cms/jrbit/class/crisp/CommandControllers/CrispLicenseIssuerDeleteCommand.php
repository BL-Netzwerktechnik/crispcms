<?php

namespace crisp\CommandControllers;

use crisp\api\Config;
use crisp\core\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CrispLicenseIssuerDeleteCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('crisp:license:issuer:delete')
            ->setDescription('Delete license issuer key pair')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force deletion');

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $io = new SymfonyStyle($input, $output);

        if (!$input->getOption('force') && !$io->confirm('Are you sure you want to delete all license key pairs?', false)) {
            return Command::INVALID;
        }

        Config::delete('license_issuer_private_key');
        Config::delete('license_issuer_public_key');
        $io->success("License key pair has been deleted!");

        return Command::SUCCESS;

    }
}
