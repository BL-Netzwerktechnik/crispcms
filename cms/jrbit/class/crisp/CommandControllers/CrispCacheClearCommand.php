<?php

namespace crisp\CommandControllers;

use crisp\core;
use crisp\core\Logger;
use crisp\core\Themes;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CrispCacheClearCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('crisp:cache:clear')
            ->setDescription('Clear crisp cache');

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        

        if (Themes::clearCache()) {
            $output->writeln("The cache has been successfully cleared!");

            return Command::SUCCESS;
        }
        $output->writeln('Failed to clear cache!');
        return Command::FAILURE;
    }
}
