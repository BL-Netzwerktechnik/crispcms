<?php

namespace crisp\CommandControllers;

use crisp\core\Logger;
use crisp\core\Themes;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CrispThemeExecuteBootFilesCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('crisp:theme:execute-boot-files')
            ->setDescription('Execute theme boot files');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $io = new SymfonyStyle($input, $output);

        if (!Themes::isInstalled()) {
            $io->error("Theme is not installed");

            return Command::FAILURE;
        }
        if (!Themes::isValid()) {
            $io->error("Theme is not mounted. Check your Docker Configuration");

            return Command::FAILURE;
        }

        Logger::startTiming($Timing);
        if (Themes::loadBootFiles()) {
            $io->success("Executed theme boot files!");
        } else {
            $io->error("Failed to execute theme boot files!");
        }
        Logger::getLogger(__METHOD__)->debug(sprintf("Operation took %sms to complete!", Logger::endTiming($Timing)));

        return Command::SUCCESS;
    }
}
