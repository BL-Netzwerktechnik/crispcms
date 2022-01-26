<?php

/*
 * Copyright (c) 2021. JRB IT, All Rights Reserved
 *
 *  @author Justin René Back <j.back@jrbit.de>
 *  @link https://github.com/jrbit/crispcms
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace crisp\core;

use Exception;
use JsonException;
use function curl_close;
use function curl_exec;
use function curl_init;
use function curl_setopt_array;

class License
{


    /*
     * Virtualization constants
     */

    /** @var int Root Server */
    public const VIRT_ROOT = 0;

    /** @var int Virtual MAchine */
    public const VIRT_VM = 1;

    /** @var int Docker Container */
    public const VIRT_DOCKER = 2;

    /** @var int Kubernetes Pod */
    public const VIRT_KUBERNETES = 3;

    /** @var int LXC Environment */
    public const VIRT_LXC = 4;


    /*
     * License Model Type
     */

    /** @var int Hostname Based License Mode */
    public const MODE_HOST = 0;

    /** @var int Seat Based License Mode */
    public const MODE_SEAT = 1;

    /** @var int Machine ID based License Mode */
    public const MODE_MID = 2;


    /*
     * License File Properties
     */
    private static string $SeatsUrl = 'https://api.jrbit.de/ISeats/v1/';
    private static string $RenewalsUrl = 'https://api.jrbit.de/ILicensing/v2/';
    private static string $PublicKey = '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAok7UfmH86b+LiP2ZW2zp
GAD/ekPdZg803iVnrmoyAQMfJ+LVgbukA9GZIIwaTnGwkeJxtLfAedvbMOPcc1KV
QsR8RNZQnmHPeb8pbHHo4aOfHrZLWhsN6Pkw0GYOLaVvhkiwHtdGIiydYWQ1fIR7
Xmm/m9YXQxCVwv3GFBD1rGyuXZ45pbmY4cKbtCoJ0aAMxob/O33XURPoV4rBWi7p
3fkcIhPZdEtrTpmEsN6o3gkxjseQ0NIwmVLbVe8h4+25px9XJlIxe4YyomKf3EU5
yQZMJYhiKPnuxiaHemik7PMg31EPDv51sv2nA4NuGeqfvc+BMtU1rCRLjuCZyOO3
re0RTQ0AuJx/Cg+kNtvz4UCtmEsYp4gMNS6scJnp5rj068wrkpt584efRHlDhAEt
Tdl86w7z6v9/Y5jbV17Yf78WYv9vJ2u2MQ+2lCdiO+ZQqpRQSl7N0047++Cu3e+K
uQOYJ6u+hmWtM0iOsSQ9KyKSPeqRBAhkz+z/6+8l8mnxJO7ZRxukkIHY99i8IkPt
yaIsrBktgKtF+Uery+VFTP/rZjCbJv7YIuN45az2T9Kf/k2pLpR4nu3kCY1W7LoL
o2riiwekWoqaoAd+rcVznDCulGJD7Hyms/yLtJaUM00DADPwYYMRLZ117u6AbsYz
Seb1Hu/BeFNHvahHjenI2BkCAwEAAQ==
-----END PUBLIC KEY-----';
    private int $_IssuedAt = 0;
    private int $_UpdatedAt = 0;
    private bool $_Active = false;
    private int $_StartsAt = 0;
    private int $_ExpiresAt = 0;
    private int $_Features = 0;
    private int $_Stale = 0;
    private int $_PingStale = 0;
    private bool $_Valid = false;
    private array $_Domains = [];
    private string $_UUID; // 0 = ID based, 1 = Seat based, 2 = Machine ID Based

    /*
     * Machine Properties
     */
    private int $_Seats = 0; // 0 = Root, 1 = VM, 2 = Docker, 3 = Kubernetes, 4 = LXC

    /*
     * Script Properties
     */
    private bool $_ZeroDay = true;
    private int $_Mode = self::MODE_HOST;
    private int $_Virtualization = self::VIRT_ROOT;
    private bool $Retrieved = false;
    private string $_InvalidReason;

    /*
     * API Properties
     */
    private string $KeyDir;
    private string $Domain;
    private bool $ThrowInsteadOfReturn;

    /**
     * License constructor.
     * @param string $KeyDir Path to the license file
     * @param bool $ThrowInsteadOfReturn Throw exceptions instead of booleans. If FALSE License->ValidateLicense() has to be called manually!
     * @param bool $CheckImmediately Validate license in constructor? Only works if $ThrowInsteadOfReturn is TRUE
     * @throws JsonException
     * @throws Exception
     */
    public function __construct(string $KeyDir, bool $ThrowInsteadOfReturn = true, bool $CheckImmediately = true)
    {

        $this->KeyDir = $KeyDir;
        $this->Domain = self::getHost();
        $this->ThrowInsteadOfReturn = $ThrowInsteadOfReturn;


        if (file_exists('/proc/1/cgroup')) {
            $proc = file_get_contents('/proc/1/cgroup');
            if (str_contains($proc, 'docker')) {
                $this->_Virtualization = 2;
            } else if (str_contains($proc, 'kubepod')) {
                $this->_Virtualization = 3;
            } else if (str_contains($proc, 'lxc')) {
                $this->_Virtualization = 4;
            }
        }

        if ($this->Domain === '') {
            throw new Exception('Failed to retrieve Domain, Server misconfigured!');
        }

        if ($this->ThrowInsteadOfReturn && $CheckImmediately) {
            $this->ValidateLicense();
        }
    }

    private static function getHost(): string
    {
        if (PHP_SAPI === 'cli') {
            return 'cli:' . gethostname();
        }
        $possibleHostSources = array('HTTP_X_FORWARDED_HOST', 'HTTP_HOST', 'SERVER_NAME', 'SERVER_ADDR');
        $sourceTransformations = array(
            'HTTP_X_FORWARDED_HOST' => function ($value) {
                $elements = explode(',', $value);
                return trim(end($elements));
            }
        );
        $host = '';
        foreach ($possibleHostSources as $source) {
            if (!empty($host)) {
                break;
            }
            if (empty($_SERVER[$source])) {
                continue;
            }
            $host = $_SERVER[$source];
            if (array_key_exists($source, $sourceTransformations)) {
                $host = $sourceTransformations[$source]($host);
            }
        }

        $host = preg_replace('/:\d+$/', '', $host);

        return trim($host);
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function ValidateLicense(): bool
    {

        if ($this->licenseIsStale()) {
            $this->RenewLicense();
            return $this->ValidateLicense();
        }

        $LicenseGet = $this->getLicense();


        if (!$LicenseGet && $this->ThrowInsteadOfReturn) {
            throw new Exception('License File Invalid');
        } else if (!$LicenseGet && !$this->ThrowInsteadOfReturn) {
            return false;
        } else if (!$this->_Valid && $this->ThrowInsteadOfReturn) {
            throw new Exception($this->_InvalidReason);
        }


        return $this->_Valid;
    }

    /**
     * @throws Exception
     */
    private function licenseIsStale(): bool
    {

        $this->getLicense();

        if ($this->isZeroDay()) {
            return true;
        }

        $StaleTime = $this->_Stale;
        if ($this->licenseFileValid()) {
            if ($this->_Stale === 0) {
                $StaleTime = strtotime('+ 10 MINUTE');
            }
            return time() > $StaleTime;
        }
        return false;
    }

    /**
     * @throws Exception
     */
    public function getLicense(): bool
    {

        $this->Retrieved = false;
        if (!$this->licenseFileValid()) {
            throw new Exception('Error Reading License File');
        }

        $pkid = openssl_pkey_get_public($this::$PublicKey);

        if (!$pkid) {
            throw new Exception('RSA Error Reading Public Key');
        }


        $LicenseContents = base64_decode(file_get_contents($this->KeyDir));

        if (!$LicenseContents) {
            throw new Exception('Error Reading License File');
        }

        $LicenseData = substr($LicenseContents, 0, -512);
        $LicenseSignature = substr($LicenseContents, -512);

        $isDataSigned = openssl_verify($LicenseData, $LicenseSignature, $pkid, 'sha256WithRSAEncryption');

        if (!$isDataSigned) {
            throw new Exception('License Data Signature Error!');
        }

        $LicenseDataDecoded = json_decode($LicenseData, true, 512, JSON_THROW_ON_ERROR);

        $this->_Active = !$LicenseDataDecoded['revoked'];

        $this->_Stale = $LicenseDataDecoded['stale'] ?? 0;
        $this->_Mode = $LicenseDataDecoded['mode'] ?? 0;
        $this->_Seats = $LicenseDataDecoded['seats'] ?? 0;
        $this->_ExpiresAt = $LicenseDataDecoded['expires_at'] ?? 0;
        $this->_IssuedAt = $LicenseDataDecoded['issued_at'] ?? 0;
        $this->_Domains = $LicenseDataDecoded['domains'] ?? [];
        $this->_UpdatedAt = $LicenseDataDecoded['updated_at'] ?? 0;
        $this->_ZeroDay = $LicenseDataDecoded['zero_day'] ?? true;
        $this->_StartsAt = $LicenseDataDecoded['starts_at'] ?? 0;
        $this->_Features = $LicenseDataDecoded['features'] ?? 0;
        $this->_UUID = $LicenseDataDecoded['uuid'];
        $this->_PingStale = @file_get_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->_UUID . '.ping') ?: 0;
        $this->Retrieved = true;

        if (!$this->_ZeroDay) {

            if ($LicenseDataDecoded['starts_at'] > 0 && (time() <= $LicenseDataDecoded['starts_at'])) {
                $this->_InvalidReason = 'License is not active yet!';
                $this->_Valid = false;
            } else if ($LicenseDataDecoded['expires_at'] > 0 && time() >= $LicenseDataDecoded['expires_at']) {
                $this->_InvalidReason = 'License has expired!';
                $this->_Valid = false;
            } else if ($LicenseDataDecoded['revoked']) {
                $this->_InvalidReason = 'License has been revoked!';
                $this->_Valid = false;
            } else if (($this->_Mode === self::MODE_HOST) && (!in_array($this->Domain, $LicenseDataDecoded['domains'], true) && (PHP_SAPI !== 'cli' && !in_array('cli:*', $LicenseDataDecoded['domains'], true)))) {
                $this->_InvalidReason = 'License Domain Mismatch: ' . $this->Domain;
                $this->_Valid = false;
            } else if (($this->_Mode === self::MODE_SEAT && $this->pingIsStale()) && !$this->PingSeatServer()) {
                $this->_InvalidReason = 'No more seats available';
                $this->_Valid = false;
            } else if (($this->_Mode === self::MODE_MID) && (!in_array($this->getMachineID(), $LicenseDataDecoded['domains'], true))) {
                $this->_InvalidReason = 'License Machine ID Mismatch: ' . $this->Domain;
                $this->_Valid = false;
            } else {
                $this->_InvalidReason = 'OK';
                $this->_Valid = true;
            }
        }

        return true;
    }

    private function licenseFileValid(): bool
    {
        $LicenseContents = file_get_contents($this->KeyDir);
        return file_exists($this->KeyDir) && strlen($LicenseContents) > 512 && strlen(substr($LicenseContents, -512)) === 512;
    }

    /**
     * @throws Exception
     */
    private function pingIsStale(): bool
    {


        $StaleTime = $this->_PingStale;

        if ($this->licenseFileValid()) {
            if ($StaleTime === 0) {
                $StaleTime = strtotime('+ 10 MINUTE');
            }
            return time() > $StaleTime || $StaleTime > strtotime('+ 30 MINUTE');
        }
        return false;
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    private function PingSeatServer(): bool
    {

        if (!$this->Retrieved) {
            $this->getLicense();
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this::$SeatsUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => http_build_query(['license' => $this->_UUID, 'machine_id' => $this->getMachineID(), 'showmeallerrors' => 'y']),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'User-Agent: JRB IT KMS'
            ],
        ]);
        $response = curl_exec($curl);

        if (curl_getinfo($curl, CURLINFO_RESPONSE_CODE) !== 200) {
            throw new Exception('Seats HTTP ERROR ' . curl_getinfo($curl, CURLINFO_RESPONSE_CODE));
        }
        curl_close($curl);

        $response = json_decode($response, false, 512, JSON_THROW_ON_ERROR);
        if (!($response->error & 0x100)) {
            throw new Exception($response->message);
        }


        $ResponseData = base64_decode($response->parameters->data);

        $DecodedData = substr($ResponseData, 0, -512);
        $DataSignature = substr($ResponseData, -512);


        $pkid = openssl_pkey_get_public($this::$PublicKey);

        if (!$pkid) {
            throw new Exception('RSA Error Reading Public Key');
        }

        $isDataSigned = openssl_verify($DecodedData, $DataSignature, $pkid, 'sha256WithRSAEncryption');

        if (!$isDataSigned) {
            throw new Exception('Seats Data Signature Error!');
        }

        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->_UUID . '.ping', strtotime('+ 10 MINUTE'));

        return $DecodedData === 'OK';

    }

    public function getMachineID(): string
    {
        $result = null;

        if (PHP_OS_FAMILY === 'Windows') {

            $output = shell_exec("diskpart /s select disk 0\ndetail disk");
            $lines = explode("\n", $output);
            $result = array_filter($lines, static function ($line) {
                return stripos($line, "ID:") !== false;
            });
            if (count($result) > 0) {
                $array = array_values($result);
                $result = array_shift($array);
                $result = explode(":", $result);
                $result = trim(end($result));
            } else {
                $result = $output;
            }
        } else if (file_exists('/etc/machine-id')) {
            $result = file_get_contents('/etc/machine-id');
        } else if (file_exists('/var/lib/dbus/machine-id')) {
            $result = file_get_contents('/var/lib/dbus/machine-id');
        }
        return md5(trim($result));
    }

    /**
     * Check if the license is a zero day license
     * @return bool TRUE if license is zero-day, otherwise FALSE
     * @throws Exception
     */
    public function isZeroDay(): bool
    {
        if (!$this->Retrieved) {
            $this->getLicense();
        }
        return $this->_ZeroDay;
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    private function RenewLicense(): void
    {

        $this->getLicense();


        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this::$RenewalsUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => http_build_query(['license' => $this->_UUID]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'User-Agent: JRB IT KMS'
            ],
        ]);
        $response = curl_exec($curl);

        if (curl_getinfo($curl, CURLINFO_RESPONSE_CODE) !== 200) {
            throw new Exception('License HTTP ERROR ' . curl_getinfo($curl, CURLINFO_RESPONSE_CODE));
        }
        curl_close($curl);

        $response = json_decode($response, false, 512, JSON_THROW_ON_ERROR);
        if (!($response->error & 0x100)) {
            throw new Exception($response->message);
        }

        $save = file_put_contents($this->KeyDir, $response->parameters->license);


        if (!$save) {
            throw new Exception("Failed to save KeyFile make sure $this->KeyDir can be written.");
        }
        $this->getLicense();
    }

    /**
     * Get the issuance timestamp of the license
     * @return int Unix Timestamp of License Issuance Date
     * @throws Exception
     */
    public function getIssuedAt(): int
    {
        if (!$this->Retrieved) {
            $this->getLicense();
        }
        return $this->_IssuedAt;
    }

    /**
     * Get the last update date of the License
     * @return int Unix Timestamp of License Update Date
     * @throws Exception
     */
    public function getUpdatedAt(): int
    {
        if (!$this->Retrieved) {
            $this->getLicense();
        }
        return $this->_UpdatedAt;
    }

    /**
     * Check if the license has been revoked
     * @return bool FALSE if the License has been revoked, otherwise TRUE
     * @throws Exception
     */
    public function isActive(): bool
    {
        if (!$this->Retrieved) {
            $this->getLicense();
        }
        return $this->_Active;
    }

    /**
     * Get the start date of the license
     * @return int Unix Timestamp of License Start Date
     * @throws Exception
     */
    public function getStartsAt(): int
    {
        if (!$this->Retrieved) {
            $this->getLicense();
        }
        return $this->_StartsAt;
    }

    /**
     * Get total seats of the license
     * @return int Total Seats of License
     * @throws Exception
     */
    public function getTotalSeats(): int
    {
        if (!$this->Retrieved) {
            $this->getLicense();
        }
        return $this->_Seats;
    }

    /**
     * Get the system virtualiation type
     * @return int system virtualiation type of machine
     * @throws Exception
     */
    public function getVirtualization(): int
    {
        if (!$this->Retrieved) {
            $this->getLicense();
        }
        return $this->_Virtualization;
    }

    /**
     * Get the expiry date of the license
     * @return int Unix Timestamp of License Expiry Date
     * @throws Exception
     */
    public function getExpiresAt(): int
    {
        if (!$this->Retrieved) {
            $this->getLicense();
        }
        return $this->_ExpiresAt;
    }

    public function toArray(): array
    {
        return [
            "issued_at" => $this->_IssuedAt,
            "updated_at" => $this->_UpdatedAt,
            "active" => $this->_Active,
            "starts_at" => $this->_StartsAt,
            "features" => $this->_Features,
            "stale" => $this->_Stale,
            "valid" => $this->_Valid,
            "domains" => $this->_Domains,
            "uuid" => $this->_UUID,
            "seats" => $this->_Seats,
            "mode" => $this->_Mode,
            "virtualization" => $this->_Virtualization,
            "machine_id" => $this->getMachineID()
        ];
    }

    /**
     * Get the bitmask of the license
     * @return int Feature Bitmask of the license
     * @throws Exception
     */
    public function getFeatures(): int
    {
        if (!$this->Retrieved) {
            $this->getLicense();
        }
        return $this->_Features;
    }

    /**
     * Get the reason why the license is invalid
     * @return string
     * @throws Exception
     */
    public function getInvalidReason(): string
    {
        if (!$this->Retrieved) {
            $this->getLicense();
        }
        return $this->_InvalidReason;
    }

    /**
     * Get the Unix Timestamp of the next refresh date
     * @return int Unix Timestamp of the next refresh date
     * @throws Exception
     */
    public function getStale(): int
    {


        if ($this->_ZeroDay) {
            return true;
        }

        if (!$this->Retrieved) {
            $this->getLicense();
        }
        return $this->_Stale;
    }

    /**
     * Check if the license is valid at all. Use this instead of License::isActive()
     * @return bool TRUE if license is valid, otherwise FALSE
     * @throws Exception
     */
    public function isValid(): bool
    {
        if (!$this->Retrieved) {
            $this->getLicense();
        }
        return $this->_Valid;
    }

    /**
     * Get array of valid domains/hosts. Hostnames start with cli:hostname
     * @return array Array of valid domains
     * @throws Exception
     */
    public function getDomains(): array
    {
        if (!$this->Retrieved) {
            $this->getLicense();
        }
        return $this->_Domains;
    }

    /**
     * Get the UUID of the license
     * @return string UUID of the license
     * @throws Exception
     */
    public function getUUID(): string
    {
        if (!$this->Retrieved) {
            $this->getLicense();
        }
        return $this->_UUID;
    }

}
