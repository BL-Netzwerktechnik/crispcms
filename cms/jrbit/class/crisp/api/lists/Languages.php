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

namespace crisp\api\lists;

use crisp\api\Language;
use crisp\core\Postgres;
use crisp\core\Logger;

/**
 * Interact with all languages stored on the server.
 */
class Languages
{

    private static ?\PDO $Database_Connection = null;

    public function __construct()
    {
        self::initDB();
    }

    /**
     * Initialize the database connection.
     */
    private static function initDB()
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $parent = \Sentry\SentrySdk::getCurrentHub()->getSpan();
        $span = null;

        if ($parent) {
            $context = new \Sentry\Tracing\SpanContext();
            $context->setOp(__METHOD__);
            $context->setDescription('Initializing Database');
            $span = $parent->startChild($context);

            \Sentry\SentrySdk::getCurrentHub()->setSpan($span);
        }

        $DB = new Postgres();
        self::$Database_Connection = $DB->getDBConnector();

        if ($span) {
            $span->finish();
            \Sentry\SentrySdk::getCurrentHub()->setSpan($parent);
        }
    }

    /**
     * Fetches all languages.
     *
     * @param  bool           $FetchIntoClass Should we fetch the result into new \crisp\api\Language()?
     * @return array|Language with all languages
     */
    public static function fetchLanguages(bool $FetchIntoClass = true): array|Language
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $parent = \Sentry\SentrySdk::getCurrentHub()->getSpan();
        $span = null;
        $returnResult = null;

        if ($parent) {
            $context = new \Sentry\Tracing\SpanContext();
            $context->setOp(__METHOD__);
            $context->setDescription('Fetching Languages');
            $span = $parent->startChild($context);

            \Sentry\SentrySdk::getCurrentHub()->setSpan($span);
        }

        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->query('SELECT * FROM Languages');

        if ($FetchIntoClass) {
            $Array = [];

            foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $Language) {
                $Array[] = new Language($Language['id']);
            }

            $returnResult = $Array;
        } else {
            $returnResult = $statement->fetchAll(\PDO::FETCH_ASSOC);
        }

        if ($span) {
            $span->finish();
            \Sentry\SentrySdk::getCurrentHub()->setSpan($parent);
        }

        return $returnResult;
    }

    /**
     * Check if a language exists by country code.
     *
     * @param  string|int $Code The language's country code
     * @return bool
     */
    public static function languageExists(string|int|null $Code): bool
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $parent = \Sentry\SentrySdk::getCurrentHub()->getSpan();
        $span = null;
        $returnResult = null;

        if ($parent) {
            $context = new \Sentry\Tracing\SpanContext();
            $context->setOp(__METHOD__);
            $context->setDescription('Checking Language Existence');
            $span = $parent->startChild($context);

            \Sentry\SentrySdk::getCurrentHub()->setSpan($span);
        }

        if ($Code === null) {
            $returnResult = false;
        } else {

            if (self::$Database_Connection === null) {
                self::initDB();
            }
            $statement = self::$Database_Connection->prepare('SELECT * FROM Languages WHERE Code = :code');
            $statement->execute([':code' => $Code]);

            $returnResult = $statement->rowCount() > 0;
        }

        if ($span) {
            $span->finish();
            \Sentry\SentrySdk::getCurrentHub()->setSpan($parent);
        }

        return $returnResult;
    }

    /**
     * Fetches a language by country code.
     *
     * @param  string              $Code           The language's country code
     * @param  bool                $FetchIntoClass Should we fetch the result into new \crisp\api\Language()?
     * @return bool|Language|array with the language
     */
    public static function getLanguageByCode(string $Code, bool $FetchIntoClass = true): bool|array|Language
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $parent = \Sentry\SentrySdk::getCurrentHub()->getSpan();
        $span = null;
        $returnResult = null;

        if ($parent) {
            $context = new \Sentry\Tracing\SpanContext();
            $context->setOp(__METHOD__);
            $context->setDescription('Fetching Language by Code');
            $span = $parent->startChild($context);

            \Sentry\SentrySdk::getCurrentHub()->setSpan($span);
        }

        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare('SELECT * FROM Languages WHERE Code = :code');
        $statement->execute([':code' => $Code]);
        if ($statement->rowCount() > 0) {
            if ($FetchIntoClass) {
                $returnResult = new Language($statement->fetch(\PDO::FETCH_ASSOC)['id']);
            } else {
                $returnResult = $statement->fetch(\PDO::FETCH_ASSOC);
            }
        } else {
            $Flag = strtolower($Code);

            if (str_contains($Flag, '_')) {
                $Flag = substr($Flag, 3);
            }

            if (Languages::createLanguage("base.language.$Code", $Code, "base.language.native.$Code", $Flag)) {
                $returnResult = self::getLanguageByCode($Code, $FetchIntoClass);
            }
        }

        if ($span) {
            $span->finish();
            \Sentry\SentrySdk::getCurrentHub()->setSpan($parent);
        }

        return $returnResult;
    }

    /**
     * Create a new language.
     *
     * @param  string $Name       The name of the language
     * @param  string $Code       The letter code of the language e.g. en-US, de, es, ru
     * @param  string $NativeName How is the language called in the native tongue?
     * @param  string $Flag       Path to the flag image on the server
     * @param  bool   $Enabled    Enable/disable the language
     * @return bool   TRUE if action was successful
     */
    public static function createLanguage(string $Name, string $Code, string $NativeName, string $Flag, bool $Enabled = true): bool
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $parent = \Sentry\SentrySdk::getCurrentHub()->getSpan();
        $span = null;
        $returnResult = null;

        if ($parent) {
            $context = new \Sentry\Tracing\SpanContext();
            $context->setOp(__METHOD__);
            $context->setDescription('Creating Language');
            $span = $parent->startChild($context);

            \Sentry\SentrySdk::getCurrentHub()->setSpan($span);
        }

        if (self::$Database_Connection === null) {
            self::initDB();
        }
        self::$Database_Connection->beginTransaction();
        $statement = self::$Database_Connection->prepare('INSERT INTO Languages (Name, Code, NativeName, Flag, Enabled) VALUES (:Name, :Code, :NativeName, :Flag, :Enabled)');
        $success = $statement->execute([':Name' => $Name, ':Code' => $Code, ':NativeName' => $NativeName, ':Flag' => $Flag, ':Enabled' => $Enabled]);

        if (!$success) {
            $returnResult = !self::$Database_Connection->rollBack();
        } else {

            $statement2 = self::$Database_Connection->prepare("SELECT table_name, column_name, data_type FROM information_schema.columns WHERE table_name = 'translations' AND column_name = '$Code';");
            $statement2->execute();
            if ($statement2->rowCount() > 0) {
                $returnResult = self::$Database_Connection->commit();
            } else {

                $statement3 = self::$Database_Connection->prepare("ALTER TABLE Translations ADD COLUMN $Code TEXT NULL");

                $success3 = $statement3->execute();

                if ($success3) {
                    $returnResult = self::$Database_Connection->commit();
                } else {
                    $returnResult = !self::$Database_Connection->rollBack();
                }

            }
        }

        if ($span) {
            $span->finish();
            \Sentry\SentrySdk::getCurrentHub()->setSpan($parent);
        }

        return $returnResult;
    }

    /**
     * Fetches a language by ID.
     *
     * @param int|string $ID The database ID of the language
     * @param bool Should we fetch the result into new \crisp\api\Language()?
     * @return bool|Language|array with the language
     */
    public static function getLanguageByID(int|string $ID, bool $FetchIntoClass = true): bool|array|Language
    {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $parent = \Sentry\SentrySdk::getCurrentHub()->getSpan();
        $span = null;
        $returnResult = null;

        if ($parent) {
            $context = new \Sentry\Tracing\SpanContext();
            $context->setOp(__METHOD__);
            $context->setDescription('Fetching Language by ID');
            $span = $parent->startChild($context);

            \Sentry\SentrySdk::getCurrentHub()->setSpan($span);
        }

        if (self::$Database_Connection === null) {
            self::initDB();
        }
        $statement = self::$Database_Connection->prepare('SELECT * FROM Languages WHERE ID = :ID');
        $statement->execute([':ID' => $ID]);
        if ($statement->rowCount() > 0) {
            if ($FetchIntoClass) {
                $returnResult = new Language($statement->fetch(\PDO::FETCH_ASSOC)['id']);
            } else {

                $returnResult = $statement->fetch(\PDO::FETCH_ASSOC);
            }
        } else {
            $returnResult = false;
        }

        if ($span) {
            $span->finish();
            \Sentry\SentrySdk::getCurrentHub()->setSpan($parent);
        }

        return $returnResult;
    }
}
