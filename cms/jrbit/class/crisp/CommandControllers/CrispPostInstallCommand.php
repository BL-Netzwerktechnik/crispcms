<?php

namespace crisp\CommandControllers;

use crisp\api\Build;
use crisp\api\Config;
use crisp\api\Helper;
use crisp\core\Logger;
use crisp\core\Environment;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CrispPostInstallCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('crisp:post-install')
            ->setDescription('Run post install actions');

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $output->writeln("Crisp has been successfully installed!");
        if (Build::getEnvironment() !== Environment::PRODUCTION->value) {
            $output->writeln(sprintf("You can access the Debug menu at %s://%s/_/debug", $_ENV["PROTO"], $_ENV["HOST"]));
        }
        $output->writeln(sprintf("Your instance id is: %s", Helper::getInstanceId()));
        $output->writeln("Release ". Build::getReleaseString());

        return Command::SUCCESS;
    }
}
