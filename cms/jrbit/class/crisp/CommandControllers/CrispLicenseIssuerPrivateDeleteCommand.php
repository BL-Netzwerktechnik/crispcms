<?php

namespace crisp\CommandControllers;

use crisp\api\Build;
use crisp\api\Config;
use crisp\api\License;
use crisp\core\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CrispLicenseIssuerPrivateDeleteCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('crisp:license:issuer:delete:private')
            ->setDescription('Delete license private issuer key')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force deletion');

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $io = new SymfonyStyle($input, $output);

        if (!License::isIssuerAvailable()) {
            $io->error('No issuer public key is installed!');

            return Command::FAILURE;
        }

        if (Build::requireLicense()) {
            $io->error("Issuers cannot be deleted on this instance!");

            return Command::FAILURE;
        }

        if (!$input->getOption('force') && !$io->confirm('Are you sure you want to delete the license issuer private key?', false)) {
            return Command::INVALID;
        }

        Config::delete('license_issuer_private_key');
        $io->success("License issuer private key has been deleted!");

        return Command::SUCCESS;

    }
}
