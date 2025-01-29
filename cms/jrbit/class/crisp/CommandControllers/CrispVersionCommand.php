<?php

namespace crisp\CommandControllers;

use crisp\api\Build;
use crisp\core\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CrispVersionCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('crisp:version')
            ->setDescription('Get current running crisp version');

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $io = new SymfonyStyle($input, $output);

        $io->note(sprintf("Crisp Version: %s", Build::getReleaseString()));
        if (Build::getBuildType() !== "Stable") {
            $io->note(sprintf("Build: %s", Build::getBuildType()));
        } else {
            $io->note(sprintf("Build: %s", Build::getBuildType()));
        }

        return Command::SUCCESS;
    }
}
