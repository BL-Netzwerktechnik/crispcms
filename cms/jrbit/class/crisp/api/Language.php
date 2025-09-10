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
use crisp\core\Postgres;
use crisp\core\Logger;

/**
 * Interact with a language.
 */
class Language extends Languages
{

    private \PDO $Database_Connection;
    public int $LanguageID;
    public mixed $Language;

    public function __construct($LanguageID)
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        $DB = new Postgres();
        $this->Database_Connection = $DB->getDBConnector();
        if (is_numeric($LanguageID)) {
            $this->LanguageID = $LanguageID;
        } else {
            $this->Language = $LanguageID;
        }
    }

    /**
     * Fetches a language's details.
     *
     * @return array|null
     */
    public function fetch(): ?array
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare('SELECT * FROM Languages WHERE ID = :ID');
        $statement->execute([':ID' => $this->LanguageID]);

        return $statement->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Enables a language.
     *
     * @return bool|null
     *
     * @see disable
     */
    public function enable(): ?bool
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare('UPDATE Languages SET Enabled = 1 WHERE ID = :ID');

        return $statement->execute([':ID' => $this->LanguageID]);
    }

    /**
     * Disables a language.
     *
     * @return bool|null
     *
     * @see enable
     */
    public function disable(): ?bool
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare('UPDATE Languages SET Enabled = 0 WHERE ID = :ID');

        return $statement->execute([':ID' => $this->LanguageID]);
    }

    /**
     * Checks wether a language is enabled or not.
     *
     * @return bool|null
     */
    public function isEnabled(): ?bool
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare('SELECT Enabled FROM Languages WHERE ID = :ID');
        $statement->execute([':ID' => $this->LanguageID]);

        return $statement->fetch(\PDO::FETCH_ASSOC)['enabled'];
    }

    /**
     * Check if the language exists in the database.
     *
     * @return bool|null
     */
    public function exists(): ?bool
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare('SELECT ID FROM Languages WHERE ID = :ID');
        $statement->execute([':ID' => $this->LanguageID]);

        return $statement->rowCount() != 0;
    }

    /**
     * Sets a new name for the language.
     *
     * @param  string    $Name The new name of the language
     * @return bool|null TRUE if successfully set, otherwise false
     */
    public function setName(string $Name): ?bool
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare('UPDATE Languages SET Name = :Name WHERE ID = :ID');

        return $statement->execute([':Name' => $Name, ':ID' => $this->LanguageID]);
    }

    /**
     * Gets the name of the language.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare('SELECT Name FROM Languages WHERE ID = :ID');
        $statement->execute([':ID' => $this->LanguageID]);

        return $statement->fetch(\PDO::FETCH_ASSOC)['name'];
    }

    /**
     * Sets the code of the language.
     *
     * @param  string    $Code The new language code
     * @return bool|null TRUE if successfully set, otherwise false
     */
    public function setCode(string $Code): ?bool
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare('UPDATE Languages SET Code = :Code WHERE ID = :ID');

        return $statement->execute([':Code' => $Code, ':ID' => $this->LanguageID]);
    }

    /**
     * Gets the code of a language.
     *
     * @return bool|null
     */
    public function getCode(): ?string
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare('SELECT Code FROM Languages WHERE ID = :ID');
        $statement->execute([':ID' => $this->LanguageID]);

        return $statement->fetch(\PDO::FETCH_ASSOC)['code'];
    }

    /**
     * Sets the new native name of the language.
     *
     * @param  string    $NativeName The new native name
     * @return bool|null TRUE if successfully set, otherwise false
     */
    public function setNativeName(string $NativeName): ?bool
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare('UPDATE Languages SET NativeName = :NativeName WHERE ID = :ID');

        return $statement->execute([':NativeName' => $NativeName, ':ID' => $this->LanguageID]);
    }

    /**
     * Gets the native name of a language.
     *
     * @return string|null
     */
    public function getNativeName(): ?string
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare('SELECT NativeName FROM Languages WHERE ID = :ID');
        $statement->execute([':ID' => $this->LanguageID]);

        return $statement->fetch(\PDO::FETCH_ASSOC)['nativename'];
    }

    /**
     * Delete a translation key.
     *
     * @param  string    $Key The translation key
     * @return bool|null
     */
    public function deleteTranslation(string $Key): ?bool
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare('DELETE FROM Translations WHERE key = :key');

        return $statement->execute([':key' => $Key]);
    }

    /**
     * Edit a translation key.
     *
     * @param  string    $Key   The translation key
     * @param  string    $Value The new value to set
     * @return bool|null
     */
    public function editTranslation(string $Key, string $Value): ?bool
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        if ($this->LanguageID === null) {
            return null;
        }

        $Code = $this->getCode();
        $statement = $this->Database_Connection->prepare("UPDATE Translations SET $Code = :value WHERE key = :key");

        return $statement->execute([':key' => $Key, ':value' => $Value]);
    }

    /**
     * Create a new translation key.
     *
     * @param  string    $Key      The translation key to create
     * @param  string    $Value    The translation text
     * @param  string    $Language
     * @return bool|null
     */
    public function newTranslation(string $Key, string $Value, string $Language = 'de'): ?bool
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        if ($this->LanguageID === null) {
            return null;
        }
        $Translation = new Translation($Language);

        if ($Translation->get($Key) === $Value) {
            return false;
        }
        if (Translation::exists($Key)) {
            return $this->editTranslation($Key, $Value);
        }

        $Code = $this->getCode();
        $statement = $this->Database_Connection->prepare("INSERT INTO Translations (key, $Code) VALUES (:key, :value)");

        return $statement->execute([':key' => $Key, ':value' => $Value]);
    }

    /**
     * Sets the flag icon of a language.
     *
     * @param  string    $Flag The flag icon name, see Themes
     * @return bool|null TRUE if successfully set, otherwise false
     */
    public function setFlag(string $Flag): ?bool
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare('UPDATE Languages SET flag = :Flag WHERE ID = :ID');

        return $statement->execute([':Flag' => $Flag, ':ID' => $this->LanguageID]);
    }

    /**
     * Gets the flag icon of a language.
     *
     * @return string|null The current path of the flag
     */
    public function getFlag(): ?string
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        if ($this->LanguageID === null) {
            return null;
        }

        $statement = $this->Database_Connection->prepare('SELECT Flag FROM Languages WHERE ID = :ID');
        $statement->execute([':ID' => $this->LanguageID]);

        return $statement->fetch(\PDO::FETCH_ASSOC)['flag'];
    }
}
