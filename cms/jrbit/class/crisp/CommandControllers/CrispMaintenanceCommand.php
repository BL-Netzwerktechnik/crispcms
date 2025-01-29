<?php

namespace crisp\CommandControllers;

use crisp\api\Config;
use crisp\core\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CrispMaintenanceCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('crisp:maintenance')
            ->setDescription('Gets or sets the maintenance status')
            ->addOption('on', 't', InputOption::VALUE_NONE, 'Turn on maintenance')
            ->addOption('off', 'f', InputOption::VALUE_NONE, 'Turn off maintenance');

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('on')) {

            if (Config::set("maintenance_enabled", true)) {
                $io->success("Maintenance Mode successfully enabled.");

                return Command::SUCCESS;
            }

            $io->error("Maintenance Mode could not be enabled.");

            return Command::FAILURE;

        }

        if ($input->getOption('off')) {

            if (Config::set("maintenance_enabled", false)) {
                $io->success("Maintenance Mode successfully disabled.");

                return Command::SUCCESS;
            }

            $io->error("Maintenance Mode could not be disabled.");

            return Command::FAILURE;

        }

        if (Config::get("maintenance_enabled")) {
            $io->warning("Maintenance Mode is currently enabled");
        } else {
            $io->success("Maintenance Mode is currently disabled");
        }

        return Command::SUCCESS;

    }
}
