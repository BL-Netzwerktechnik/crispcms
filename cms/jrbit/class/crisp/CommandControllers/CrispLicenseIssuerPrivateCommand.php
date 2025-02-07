<?php

namespace crisp\CommandControllers;

use crisp\api\Config;
use crisp\api\License;
use crisp\core\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CrispLicenseIssuerPrivateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('crisp:license:issuer:private')
            ->setDescription('Get license private issuer key');

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $io = new SymfonyStyle($input, $output);

        if (!License::isIssuerPrivateAvailable()) {
            $io->error('No issuer private key is installed!');

            return Command::FAILURE;
        }
        $output->write(Config::get('license_issuer_private_key'));

        return Command::SUCCESS;

    }
}
