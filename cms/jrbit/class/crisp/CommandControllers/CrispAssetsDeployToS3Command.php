<?php

namespace crisp\CommandControllers;

use Aws\S3\S3Client;
use crisp\api\Helper;
use crisp\core\Logger;
use crisp\core\Themes;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CrispAssetsDeployToS3Command extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('crisp:assets:deploy-to-s3')
            ->setDescription('Deploy assets to configured s3 server');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        if (!isset($_ENV["ASSETS_S3_BUCKET"], $_ENV["ASSETS_S3_REGION"], $_ENV["ASSETS_S3_BUCKET"], $_ENV["ASSETS_S3_ACCESS_KEY"], $_ENV["ASSETS_S3_SECRET_KEY"])) {
            $output->writeln("Missing one of the following environment variables to deploy to s3: ASSETS_S3_BUCKET, ASSETS_S3_REGION, ASSETS_S3_BUCKET, ASSETS_S3_ACCESS_KEY, ASSETS_S3_SECRET_KEY");

            return Command::FAILURE;
        }

        $conn = [
            'version' => 'latest',
            'region'  => $_ENV["ASSETS_S3_REGION"],
        ];

        if (isset($_ENV["ASSETS_S3_HOST"])) {
            $conn["endpoint"] = $_ENV["ASSETS_S3_HOST"];
        }
        if (isset($_ENV["ASSETS_S3_ACCESS_KEY"],  $_ENV["ASSETS_S3_SECRET_KEY"])) {
            $conn["credentials"] = [
                'key'    => $_ENV["ASSETS_S3_ACCESS_KEY"],
                'secret' => $_ENV["ASSETS_S3_SECRET_KEY"],
            ];
        }
        $constructedurl = Helper::getS3Url($_ENV["ASSETS_S3_BUCKET"], $_ENV["ASSETS_S3_REGION"], $_ENV["ASSETS_S3_URL"]);

        $s3 = new S3Client($conn);

        foreach (Helper::getDirRecursive(Themes::getThemeDirectory() . "/assets") as $file) {
            $newFileName = substr($file, strlen(Themes::getThemeDirectory()));

            $s3->deleteObject([
                'Bucket' => $_ENV["ASSETS_S3_BUCKET"],
                'Key'    => $newFileName,
            ]);

            $insert = $s3->putObject([
                'Bucket' => $_ENV["ASSETS_S3_BUCKET"],
                'Key'    => $newFileName,
                'SourceFile'   => $file,
                'ContentType' => Helper::detectMimetype($file),
            ]);

            if ($insert['@metadata']['statusCode'] === 200) {
                $output->writeln("Uploaded $newFileName to $constructedurl$newFileName");
            } else {
                $output->writeln("Failed uploading $newFileName to $constructedurl$newFileName");
            }
        }

        return Command::SUCCESS;
    }
}
