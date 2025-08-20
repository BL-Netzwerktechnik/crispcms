<?php

namespace crisp\CommandControllers;

use crisp\core\Logger;
use crisp\core\Themes;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CrispThemeCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('crisp:theme')
            ->setDescription('Theme management')
            ->addOption('install', 'i', InputOption::VALUE_NONE, 'Install crisp theme')
            ->addOption('uninstall', 'u', InputOption::VALUE_NONE, 'Uninstall crisp theme');

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $io = new SymfonyStyle($input, $output);

        if ($input->getOption("install")) {

            if (Themes::isInstalled()) {
                $io->warning("Theme is already installed");

                return Command::SUCCESS;
            }
            if (!Themes::isValid()) {
                $io->error("Theme is not mounted. Check your Docker Configuration");

                return Command::FAILURE;
            }

            Logger::startTiming($Timing);
            if (Themes::install()) {
                $io->success("Theme successfully installed!");
            } else {
                $io->error("Failed to install Theme!");
            }
            Logger::getLogger(__METHOD__)->debug(sprintf("Operation took %sms to complete!", Logger::endTiming($Timing)));

            return Command::SUCCESS;
        } elseif ($input->getOption('uninstall')) {

            if (!Themes::isInstalled()) {
                $io->error("Theme is not installed");

                return false;
            }
            if (!Themes::isValid()) {
                $io->error("Theme is not mounted. Check your Docker Configuration");

                return false;
            }

            Logger::startTiming($Timing);
            if (Themes::uninstall()) {
                $io->success("Theme successfully uninstalled!");
            } else {
                $io->error("Failed to uninstall Theme!");
            }
            Logger::getLogger(__METHOD__)->debug(sprintf("Operation took %sms to complete!", Logger::endTiming($Timing)));

            return Command::SUCCESS;
        }

        if (Themes::isInstalled()) {
            $io->success('Theme is installed!');

            return Command::SUCCESS;
        }

        $io->error('Theme is not installed!');

        return Command::FAILURE;
    }
}
