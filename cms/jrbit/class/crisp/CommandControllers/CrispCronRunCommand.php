<?php

namespace crisp\CommandControllers;

use crisp\core\Cron;
use crisp\core\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CrispCronRunCommand extends Command
{
    protected function configure(): void
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        $this
            ->setName('crisp:cron:run')
            ->setDescription('Run the cron scheduler');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }

        $output->writeln('Running scheduler');

        Cron::get()->run();

        $output->writeln('Cron scheduler run');

        return Command::SUCCESS;
    }
}
