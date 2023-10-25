<?php

namespace crisp\commands;

use Aws\S3\S3Client;
use crisp\api\Helper;
use crisp\core\Logger;
use crisp\core\Themes;
use splitbrain\phpcli\Options;

class Assets
{
    public static function run(\CLI $minimal, Options $options): bool
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]);
        if ($options->getOpt("deploy-to-s3")) {

            if (!isset($_ENV["ASSETS_S3_BUCKET"], $_ENV["ASSETS_S3_REGION"], $_ENV["ASSETS_S3_BUCKET"], $_ENV["ASSETS_S3_ACCESS_KEY"], $_ENV["ASSETS_S3_SECRET_KEY"])) {
                $minimal->fatal("Missing one of the following environment variables to deploy to s3: ASSETS_S3_BUCKET, ASSETS_S3_REGION, ASSETS_S3_BUCKET, ASSETS_S3_ACCESS_KEY, ASSETS_S3_SECRET_KEY");

                return false;
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
                    $minimal->success("Uploaded $newFileName to $constructedurl$newFileName");
                } else {
                    $minimal->error("Failed uploading $newFileName to $constructedurl$newFileName");
                }
            }

            return true;
        }

        $minimal->error("No action");

        return true;
    }
}
