<?php

namespace crisp\CommandControllers;

use crisp\api\Helper;
use crisp\core;
use crisp\core\Logger;
use crisp\core\Themes;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CrispInstanceIdCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('crisp:instance:id')
            ->setDescription('Get the id of your instance');

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        
        $output->writeln(Helper::getInstanceId());
        return Command::SUCCESS;
    }
}
