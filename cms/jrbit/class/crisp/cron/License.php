<?php

namespace crisp\cron;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use crisp\api\Build;
use crisp\api\Config;
use crisp\api\Helper;
use crisp\api\License as ApiLicense;
use crisp\core;
use crisp\core\Crypto;
use crisp\core\Environment;
use crisp\core\Logger;
use splitbrain\phpcli\Options;

class License
{
    public static function pull(): bool
    {
        if (!Build::requireLicenseServer()) {
            Logger::getLogger(__METHOD__)->warning("This instance does not have a license server configured!");
            return true;
        }

        $License = ApiLicense::fromLicenseServer();

        if ($License !== false && !$License->isValid()) {


            if ($License) {
                Logger::getLogger(__METHOD__)->error("The following errors occurred:");
                foreach ($License->getErrors() as $error) {
                    Logger::getLogger(__METHOD__)->error($error);
                }
            }

            if(ApiLicense::isLicenseAvailable()){
                $License->uninstall();
            }

            Logger::getLogger(__METHOD__)->error("Could not pull license!");
            return false;
        }

        Logger::getLogger(__METHOD__)->info("Successfully pulled license!");
        return true;
    }
}
