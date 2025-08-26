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
use crisp\core\Logger;
use crisp\core\Postgres;

/**
 * Access the translations of the CMS.
 */
class Translation
{

    /**
     * The Language code.
     *
     * @var string|null
     */
    public static ?string $Language = null;
    private static ?\PDO $Database_Connection = null;

    /**
     * Sets the language code and inits the database connection for further use of functions in this class.
     *
     * @param string|null $Language The Language code or null
     */
    public function __construct(?string $Language)
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        self::$Language = $Language;
    }

    /**
     * Same as \crisp\api\lists\Languages()->fetchLanguages().
     *
     * @param  bool           $FetchIntoClass Should the result be fetched into a \crisp\api\Language class
     * @return Language|array depending on the $FetchIntoClass parameter
     * @uses  \crisp\api\lists\Languages()
     */
    public static function listLanguages(bool $FetchIntoClass = true): array|Language
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        $Languages = new Languages();

        return $Languages::fetchLanguages($FetchIntoClass);
    }

    /**
     * Retrieves all translations with key and language code.
     *
     * @return array containing all translations on the server
     */
    public static function listTranslations(): array
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->query("SELECT * FROM Translations");
        if ($statement->rowCount() > 0) {
            return $statement->fetchAll(\PDO::FETCH_ASSOC);
        }

        return [];
    }

    

    public static function uninstallAllTranslations(): bool
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        try {
            $Translations = Translation::listTranslations();

            $Language = Languages::getLanguageByCode("de");

            foreach ($Translations as $Key => $Translation) {

                Logger::getLogger(__METHOD__)->debug("Deleting translation key " . $Translation["key"]);
                if ($Language->deleteTranslation($Translation["key"])) {
                    Logger::getLogger(__METHOD__)->debug("Deleted translation key " . $Translation["key"]);
                }
            }
        } catch (\PDOException $ex) {
            Logger::getLogger(__METHOD__)->error("Error uninstalling translations", (array)$ex);
        }

        return true;
    }


    /**
     * @param string $ThemeName
     * @param \stdClass ThemeMetadata
     * @return bool
     */
    public static function installTranslations(string $LanguageFile): void
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        if (!file_exists($LanguageFile)) {
            Logger::getLogger(__METHOD__)->error("Language file $LanguageFile not found!");
            return;
        }


        $_processed = [];

        $Language = Languages::getLanguageByCode(substr(basename($LanguageFile), 0, -5));

        if (!$Language) {
            Logger::getLogger(__METHOD__)->error(sprintf("%s not found!", substr(basename($LanguageFile), 0, -5)));
            return;
        }
        foreach (json_decode(file_get_contents($LanguageFile), true, 512, JSON_THROW_ON_ERROR) as $Key => $Value) {
            try {

                if ($Language->newTranslation($Key, $Value, substr(basename($LanguageFile), 0, -5))) {
                    $_processed[] = $Key;
                    Logger::getLogger(__METHOD__)->debug(sprintf("Installed translation key %s", $Key));
                } elseif (defined("CRISP_CLI")) {
                    Logger::getLogger(__METHOD__)->warning(sprintf("Did not Install translation key %s", $Key));
                }
            } catch (\PDOException $ex) {
                if (defined("CRISP_CLI")) {
                    Logger::getLogger(__METHOD__)->error($ex);
                }
            }
        }

        Logger::getLogger(__METHOD__)->notice(sprintf("Successfully Updated %s translation keys", count($_processed)));
    }

    /**
     * Inits DB.
     */
    private static function initDB()
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        $DB = new Postgres();
        self::$Database_Connection = $DB->getDBConnector();
    }

    /**
     * Retrieves all translations for the specified self::$Language.
     *
     * @return array containing all translations for the self::$Language
     * @uses self::$Language
     */
    public static function fetchAll(): array
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->query("SELECT * FROM Translations");
        if ($statement->rowCount() > 0) {

            $Translations = $statement->fetchAll(\PDO::FETCH_ASSOC);

            $Array = [];

            foreach (Languages::fetchLanguages() as $Language) {
                $Array[$Language->getCode()] = [];
                foreach ($Translations as $Item) {
                    $Array[$Language->getCode()][$Item["key"]] = $Item[$Language->getCode()];
                }
            }

            return $Array;
        }

        return [];
    }

    /**
     * Fetch all translations by key.
     *
     * @param  string $Key The letter code
     * @return array
     */
    public static function fetchAllByKey(string $Key): array
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->query("SELECT * FROM Translations");
        if ($statement->rowCount() > 0) {

            $Translations = $statement->fetchAll(\PDO::FETCH_ASSOC);

            $Array = [];
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

        return [];
    }

    /**
     * Check if a translation exists by key.
     *
     * @param  string $Key The translation key
     * @return bool
     */
    public static function exists(string $Key): bool
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare("SELECT * FROM Translations WHERE key = :key");
        $statement->execute([":key" => $Key]);

        return $statement->rowCount() > 0;
    }

    /**
     * Fetches translations for the specified key.
     *
     * @param  string $Key         The translation key
     * @param  int    $Count       used for the plural and singular retrieval of translations, also exposes {{ count }} in templates
     * @param  array  $UserOptions Custom array used for templating
     * @return string The translation or the key if it doesn't exist
     */
    public static function fetch(string $Key, int $Count = 1, array $UserOptions = []): string
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        if (!isset(self::$Language)) {
            self::$Language = Helper::getLocale();
        }

        $UserOptions["{{ count }}"] = $Count;

        return nl2br(ngettext(self::get($Key, $UserOptions), self::getPlural($Key, $UserOptions), $Count));
    }

    /**
     * Fetches all singular translations for the specified key.
     *
     * @param  string $Key         The translation key
     * @param  array  $UserOptions Custom array used for templating
     * @return string The translation or the key if it doesn't exist
     *
     * @see getPlural
     * @see fetch
     */
    public static function get(string $Key, array $UserOptions = []): string
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        if (self::$Database_Connection === null) {
            self::initDB();
        }

        $statement = self::$Database_Connection->prepare("SELECT * FROM Translations WHERE key = :Key");
        $statement->execute([
            ":Key" => $Key,
            // ":Language" => $this->Language
        ]);
        if ($statement->rowCount() > 0) {

            $Translation = $statement->fetch(\PDO::FETCH_ASSOC);

            if (!isset($Translation[strtolower(self::$Language)])) {
                if (self::$Language === ($_ENV['DEFAULT_LOCALE'] ?? 'en') || !$Translation[$_ENV['DEFAULT_LOCALE'] ?? 'en']) {
                    return $Key;
                }

                return strtr($Translation[$_ENV['DEFAULT_LOCALE'] ?? 'en'], $UserOptions);
            }

            return strtr($Translation[strtolower(self::$Language)], $UserOptions);
        }

        return $Key;
    }

    /**
     * Fetches all plural translations for the specified key.
     *
     * @param  string $Key         The translation key
     * @param  array  $UserOptions Custom array used for templating
     * @return string The translation or the key if it doesn't exist
     *
     * @see get
     * @see fetch
     */
    public static function getPlural(string $Key, array $UserOptions = []): string
    {

        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        if (self::$Database_Connection === null) {
            self::initDB();
        }

        $statement = self::$Database_Connection->prepare("SELECT * FROM Translations WHERE key = :Key");
        $statement->execute([
            ":Key" => $Key . ".plural",
            // ":Language" => $this->Language
        ]);
        if ($statement->rowCount() > 0) {
            $Translation = $statement->fetch(\PDO::FETCH_ASSOC);

            if ($Translation[strtolower(self::$Language)] === null) {
                if (self::$Language === $_ENV['DEFAULT_LOCALE'] ?? 'en' || !$Translation[$_ENV['DEFAULT_LOCALE'] ?? 'en']) {
                    return $Key . ".plural";
                }

                return strtr($Translation[$_ENV['DEFAULT_LOCALE'] ?? 'en'], $UserOptions);
            }

            return strtr($Translation[strtolower(self::$Language)], $UserOptions);
        }

        return $Key . ".plural";
    }
}
