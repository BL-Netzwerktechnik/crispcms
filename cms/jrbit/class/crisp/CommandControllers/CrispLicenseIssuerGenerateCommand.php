<?php

namespace crisp\CommandControllers;

use crisp\api\Build;
use crisp\api\License;
use crisp\core\Environment;
use crisp\core\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CrispLicenseIssuerGenerateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('crisp:license:issuer:generate')
            ->setDescription('Generate an issuer key pair');

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $io = new SymfonyStyle($input, $output);

        if (Build::requireLicense() && Build::getEnvironment() !== Environment::DEVELOPMENT) {
            $io->error("Licenses cannot be generated on this instance!");

            return Command::FAILURE;
        }

        if (License::isIssuerPrivateAvailable()) {
            if (!$io->confirm('An issuer private key already exists, do you want to overwrite it?', false)) {
                return Command::INVALID;
            }
        }

        if (License::generateIssuer()) {
            $io->success("Issuer Key pair has been generated");

            return Command::SUCCESS;
        }

        $io->error("Failed to generate issuer key pair!");

        return Command::FAILURE;
    }
}
