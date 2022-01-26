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


namespace crisp\api;

use crisp\api\lists\Languages;
use crisp\core\Bitmask;
use crisp\core\Postgres;
use crisp\core\RESTfulAPI;
use PDO;

/**
 * Access the translations of the CMS
 */
class Translation
{

    /**
     * The Language code
     * @var string|null
     */
    public static ?string $Language = null;
    private static ?PDO $Database_Connection = null;

    /**
     * Sets the language code and inits the database connection for further use of functions in this class
     * @param string|null $Language The Language code or null
     */
    public function __construct(?string $Language)
    {
        self::$Language = $Language;
    }

    /**
     * Same as \crisp\api\lists\Languages()->fetchLanguages()
     * @param bool $FetchIntoClass Should the result be fetched into a \crisp\api\Language class
     * @return Language|array depending on the $FetchIntoClass parameter
     * @uses  \crisp\api\lists\Languages()
     */
    public static function listLanguages(bool $FetchIntoClass = true): array|Language
    {
        $Languages = new Languages();
        return $Languages::fetchLanguages($FetchIntoClass);
    }

    /**
     * Retrieves all translations with key and language code
     * @return array containing all translations on the server
     */
    public static function listTranslations(): array
    {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->query("SELECT * FROM Translations");
        if ($statement->rowCount() > 0) {
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    /**
     * Inits DB
     */
    private static function initDB()
    {
        $DB = new Postgres();
        self::$Database_Connection = $DB->getDBConnector();
    }

    /**
     * Retrieves all translations for the specified self::$Language
     * @return array containing all translations for the self::$Language
     * @uses self::$Language
     */
    public static function fetchAll(): array
    {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->query("SELECT * FROM Translations");
        if ($statement->rowCount() > 0) {

            $Translations = $statement->fetchAll(PDO::FETCH_ASSOC);

            $Array = array();

            foreach (lists\Languages::fetchLanguages() as $Language) {
                $Array[$Language->getCode()] = array();
                foreach ($Translations as $Item) {
                    $Array[$Language->getCode()][$Item["key"]] = $Item[$Language->getCode()];
                }
            }

            return $Array;
        }
        return array();
    }

    /**
     * Fetch all translations by key
     * @param string $Key The letter code
     * @return array
     */
    public static function fetchAllByKey(string $Key): array
    {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->query("SELECT * FROM Translations");
        if ($statement->rowCount() > 0) {

            $Translations = $statement->fetchAll(PDO::FETCH_ASSOC);

            $Array = array();
            foreach ($Translations as $Item) {
                if (str_contains($Item["key"], "plugin.")) {
                    continue;
                }
                if ($Item[$Key] === null) {
                    continue;
                }
                $Array[$Key][$Item["key"]] = $Item[$Key];
            }

            return $Array[$Key];
        }
        return array();
    }

    /**
     * Check if a translation exists by key
     * @param string $Key The translation key
     * @return bool
     */
    public static function exists(string $Key): bool
    {
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM Translations WHERE key = :key");
        $statement->execute(array(":key" => $Key));
        return ($statement->rowCount() > 0);
    }

    /**
     * Fetches translations for the specified key
     * @param string $Key The translation key
     * @param int $Count Used for the plural and singular retrieval of translations, also exposes {{ count }} in templates.
     * @param array $UserOptions Custom array used for templating
     * @return string The translation or the key if it doesn't exist
     */
    public static function fetch(string $Key, int $Count = 1, array $UserOptions = array()): string
    {

        if (!isset(self::$Language)) {
            self::$Language = Helper::getLocale();
        }


        if (isset($GLOBALS["route"]->GET["debug"]) && $GLOBALS["route"]->GET["debug"] = "translations") {
            return "$Key:" . self::$Language;
        }

        $UserOptions["{{ count }}"] = $Count;

        return nl2br(ngettext(self::get($Key, $UserOptions), self::getPlural($Key, $UserOptions), $Count));
    }

    /**
     * Fetches all singular translations for the specified key
     * @param string $Key The translation key
     * @param array $UserOptions Custom array used for templating
     * @return string The translation or the key if it doesn't exist
     * @see getPlural
     * @see fetch
     */
    public static function get(string $Key, array $UserOptions = array()): string
    {

        if (self::$Database_Connection === null) {
            self::initDB();
        }

        $GlobalOptions = [];
        foreach (Config::list(true) as $Item) {
            $GlobalOptions["{{ config.{$Item['key']} }}"] = $Item["value"];
        }

        $Options = array_merge($UserOptions, $GlobalOptions);


        $statement = self::$Database_Connection->prepare("SELECT * FROM Translations WHERE key = :Key");
        $statement->execute(array(
            ":Key" => $Key,
            //":Language" => $this->Language
        ));
        if ($statement->rowCount() > 0) {

            $Translation = $statement->fetch(PDO::FETCH_ASSOC);

            if (!isset($Translation[strtolower(self::$Language)])) {
                if (self::$Language === ($_ENV['DEFAULT_LOCALE'] ?? 'en') || !$Translation[$_ENV['DEFAULT_LOCALE'] ?? 'en']) {
                    return $Key;
                }

                return $Translation[$_ENV['DEFAULT_LOCALE'] ?? 'en'];
            }

            return strtr($Translation[strtolower(self::$Language)], $Options);
        }
        return $Key;
    }

    /**
     * Fetches all plural translations for the specified key
     * @param string $Key The translation key
     * @param array $UserOptions Custom array used for templating
     * @return string The translation or the key if it doesn't exist
     * @see get
     * @see fetch
     */
    public static function getPlural(string $Key, array $UserOptions = array()): string
    {

        if (self::$Database_Connection === null) {
            self::initDB();
        }

        $GlobalOptions = [];

        foreach (Config::list(true) as $Item) {
            $GlobalOptions["{{ config.{$Item['key']} }}"] = $Item["value"];
        }

        $Options = array_merge($UserOptions, $GlobalOptions);


        $statement = self::$Database_Connection->prepare("SELECT * FROM Translations WHERE key = :Key");
        $statement->execute(array(
            ":Key" => $Key . ".plural",
            //":Language" => $this->Language
        ));
        if ($statement->rowCount() > 0) {
            $Translation = $statement->fetch(PDO::FETCH_ASSOC);

            if ($Translation[strtolower(self::$Language)] === null) {
                if (self::$Language === $_ENV['DEFAULT_LOCALE'] ?? 'en' || !$Translation[$_ENV['DEFAULT_LOCALE'] ?? 'en']) {
                    return $Key . ".plural";
                }
                return strtr($Translation[$_ENV['DEFAULT_LOCALE'] ?? 'en'], $Options);
            }

            return strtr($Translation[strtolower(self::$Language)], $Options);
        }
        return $Key . ".plural";
    }

}
