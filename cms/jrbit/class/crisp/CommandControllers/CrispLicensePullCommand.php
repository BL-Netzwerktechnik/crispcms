<?php

namespace crisp\CommandControllers;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use crisp\api\Build;
use crisp\api\Config;
use crisp\api\Helper;
use crisp\api\License;
use crisp\core;
use crisp\core\Environment;
use crisp\core\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CrispLicensePullCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('crisp:license:pull')
            ->setDescription('Pull a license via key')
            ->addOption('key', 'k', InputOption::VALUE_OPTIONAL, 'License Key');

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        $io = new SymfonyStyle($input, $output);

        var_dump($_ENV["LICENSE_SERVER"]);
        
        if (!Build::requireLicenseServer()) {
            $io->error("This instance does not have a license server configured!");
            return Command::FAILURE;
        }

        $License = License::fromLicenseServer($input->getOption("key") !== false ? $input->getOption("key") : null);

        if (!$License || !$License->isValid()) {


            if ($License) {
                $io->note("The following errors occurred:");
                foreach ($License->getErrors() as $error) {
                    $io->warning($error);
                }
            }

            if(License::isLicenseAvailable()){
                $License->uninstall();
            }

            $io->error("Could not pull license!");
            return Command::FAILURE;
        }

        $io->success("Successfully pulled license!");
    
        return Command::SUCCESS;
    }
}
