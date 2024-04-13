<?php

namespace crisp\CommandControllers;

use crisp\core\Cron;
use crisp\core\Logger;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TypeError;

class CrispCronRunCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('crisp:cron:run')
            ->setDescription('Run the cron scheduler');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        

        $output->writeln('Running scheduler');

        Cron::get()->run();

        $output->writeln('Cron scheduler run');

        return Command::SUCCESS;
    }
}
