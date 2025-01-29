<?php

namespace crisp\CommandControllers;

use crisp\core;
use crisp\core\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CrispCheckPermissionsCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('crisp:check-permissions')
            ->setDescription('Check filesystem permissions');

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $formatter = $this->getHelper('formatter');

        $exitCode = Command::SUCCESS;

        if (!is_writable(core::PERSISTENT_DATA)) {
            $output->writeln(sprintf("Directory %s is not writable!", core::PERSISTENT_DATA));
            $exitCode = Command::FAILURE;
        } else {
            $output->writeln(sprintf("Directory %s is writable!", core::PERSISTENT_DATA));
        }

        if (!is_writable(core::PERSISTENT_DATA . "/.instance_id")) {
            $exitCode = Command::FAILURE;
            $output->writeln(sprintf("File %s is not writable!", core::PERSISTENT_DATA . "/.instance_id"));
        } else {
            $output->writeln(sprintf("File %s is writable!", core::PERSISTENT_DATA . "/.instance_id"));
        }

        if (!is_writable(core::CACHE_DIR)) {
            $exitCode = Command::FAILURE;
            $output->writeln(sprintf("Directory %s is not writable!", core::CACHE_DIR));
        } else {
            $output->writeln(sprintf("Directory %s is writable!", core::CACHE_DIR));
        }

        return $exitCode;
    }
}
