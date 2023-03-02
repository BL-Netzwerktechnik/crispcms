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

class License
{

    /*
     * License File Properties
     */
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
    private int $_StartsAt = 0;
    private int $_ExpiresAt = 0;
    private int $_Features = 0;
    private bool $_Valid = false;
    private bool $Retrieved = false;
    private array $_Domains = [];
    private int $_Plan = -1;

    private static array $Plans = [
        "free" => -1,
        "teams" => 1,
        "enterprise" => 2
    ];

    /*
     * Script Properties
     */
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

        $this->_ExpiresAt = $LicenseDataDecoded['expires_at'] ?? 0;
        $this->_IssuedAt = $LicenseDataDecoded['issued_at'] ?? 0;
        $this->_Domains = $LicenseDataDecoded['domains'] ?? [];
        $this->_UpdatedAt = $LicenseDataDecoded['updated_at'] ?? 0;
        $this->_StartsAt = $LicenseDataDecoded['starts_at'] ?? 0;
        $this->_Plan = $LicenseDataDecoded['plan'] ?? -1;


            if ($LicenseDataDecoded['starts_at'] > 0 && (time() <= $LicenseDataDecoded['starts_at'])) {
                $this->_InvalidReason = 'License is not active yet!';
                $this->_Valid = false;
            } else if ($LicenseDataDecoded['expires_at'] > 0 && time() >= $LicenseDataDecoded['expires_at']) {
                $this->_InvalidReason = 'License has expired!';
                $this->_Valid = false;
            } else {
                $this->_InvalidReason = 'OK';
                $this->_Valid = true;
            }
        return true;
    }

    private function licenseFileValid(): bool
    {
        $LicenseContents = file_get_contents($this->KeyDir);
        return file_exists($this->KeyDir) && strlen($LicenseContents) > 512 && strlen(substr($LicenseContents, -512)) === 512;
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
            "starts_at" => $this->_StartsAt,
            "features" => $this->_Features,
            "valid" => $this->_Valid,
            "domains" => $this->_Domains,
            "plan" => $this->_Plan
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

    public function getPlan(): int
    {
        if (!$this->Retrieved) {
            $this->getLicense();
        }
        return $this->_Plan;
    }

}
