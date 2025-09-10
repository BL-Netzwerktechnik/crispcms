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

use crisp\api\Helper;
use crisp\Controllers\EventController;
use crisp\Events\MigrationEvents;
use PDO;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Crisp DB Migration Class.
 *
 * @since 0.0.8-beta.RC1
 */
class Migrations
{

    /**
     * The PDO Database.
     *
     * @var \PDO
     */
    protected \PDO $Database;

    /* Data types */

    public const DB_VARCHAR = 'varchar(255)';
    public const DB_TEXT = 'text';
    public const DB_INTEGER = 'integer';
    public const DB_TIMESTAMP = 'timestamp';
    public const DB_BOOL = 'smallint';
    public const DB_LONGTEXT = 'text';
    public const DB_BIGINT = 'bigint';

    /* Keys */
    public const DB_PRIMARYKEY = 'PRIMARY';
    public const DB_UNIQUEKEY = 'UNIQUE';

    public function __construct()
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        $DB = new Postgres();
        $this->Database = $DB->getDBConnector();
    }

    /**
     * Begin a MySQL Transaction.
     *
     * @return bool
     *
     * @since 0.0.8-beta.RC2
     */
    protected function begin(): bool
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        Logger::getLogger(__METHOD__)->debug('Initiating Transaction...');
        if ($this->Database->beginTransaction()) {
            Logger::getLogger(__METHOD__)->debug('Transaction initiated!');

            return true;
        }
        Logger::getLogger(__METHOD__)->error('Failed to initiate transaction!');

        return false;
    }

    /**
     * Rollback a MySQL Transaction.
     *
     * @return bool
     *
     * @since 0.0.8-beta.RC2
     */
    protected function rollback(): bool
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        Logger::getLogger(__METHOD__)->info('Rolling back transaction...');
        EventController::getEventDispatcher()->dispatch(new Event(), MigrationEvents::BEFORE_ROLLBACK);
        if ($this->Database->rollBack()) {
            EventController::getEventDispatcher()->dispatch(new Event(), MigrationEvents::AFTER_ROLLBACK);
            Logger::getLogger(__METHOD__)->info('Rolled back transaction!');

            return true;
        }
        Logger::getLogger(__METHOD__)->error('Failed to rollback transaction!');

        return false;
    }

    /**
     * End/commit a MySQL Transaction.
     *
     * @return bool
     *
     * @since 0.0.8-beta.RC2
     */
    protected function end(): bool
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        Logger::getLogger(__METHOD__)->info('Committing Transaction...');

        EventController::getEventDispatcher()->dispatch(new Event(), MigrationEvents::BEFORE_COMMIT);
        if ($this->Database->commit()) {
            EventController::getEventDispatcher()->dispatch(new Event(), MigrationEvents::AFTER_COMMIT);
            Logger::getLogger(__METHOD__)->info('Transaction committed!');

            return true;
        }
        Logger::getLogger(__METHOD__)->error('Failed to commit Transaction!');

        return false;
    }

    /**
     * Check if a migration is already installed.
     *
     * @param string $file The migration filename to check. Don't use the extension in the filename.
     *
     * @see basename
     * @since 0.0.8-beta.RC2
     *
     * @return bool
     */
    public function isMigrated(string $file, ?string $plugin = null): bool
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        if ($file === 'createmigration') {
            return $this->tableExists('schema_migration');
        }

        try {
            Logger::getLogger(__METHOD__)->debug(sprintf("SELECT * FROM schema_migration WHERE file = '$file' AND plugin %s", $plugin !== null ? "= '$plugin'" : 'IS NULL'));
            $statement = $this->Database->prepare(sprintf('SELECT * FROM schema_migration WHERE file = :file AND plugin %s', $plugin !== null ? '= :plugin' : 'IS NULL'));

            if ($plugin === null) {
                $statement->execute([':file' => $file]);
            } else {
                $statement->execute([':file' => $file, ':plugin' => $plugin]);
            }

            return $statement->rowCount() > 0;
        } catch (\Exception $ex) {
            Logger::getLogger(__METHOD__)->error($ex);

            return false;
        }
    }

    public function tableExists(string $tableName): bool
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        try {
            $statement = $this->Database->prepare('SELECT to_regclass(:tableName);');

            $statement->execute([':tableName' => $tableName]);

            return $statement->rowCount() > 0;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Begin the migration of the database.
     *
     * @param string      $Dir    The directory base to look for migrations. e.g Setting "plugin" will look in "plugin/migrations"
     * @param string|null $Plugin If the migration has been done for a plugin, this is the name
     *
     * @since 0.0.8-beta.RC2
     */
    public function migrate(string $Dir = __DIR__ . '/../', ?string $Plugin = null): void
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        Logger::getLogger(__METHOD__)->info('Starting Database Migration');
        if (!file_exists($Dir)) {
            Logger::getLogger(__METHOD__)->error(sprintf('Directory "%s" does not exist! Cannot perform migrations!', $Dir));

            return;
        }
        if (!file_exists("$Dir/migrations/")) {
            Logger::getLogger(__METHOD__)->warning(sprintf('No "migrations" directory existed within "%s", cannot perform migrations!', realpath($Dir)));

            return;
        }
        $files = glob("$Dir/migrations/*.{php}", GLOB_BRACE);

        natsort($files);

        foreach ($files as $file) {
            if (basename($file) == 'template.php') {
                continue;
            }

            $MigrationName = substr(basename($file), 0, -4);

            if ($this->isMigrated($MigrationName, $Plugin)) {
                Logger::getLogger(__METHOD__)->warning("$MigrationName is already migrated, skipping.");
                continue;
            }

            $Class = "\crisp\migrations\\" . explode('_', $MigrationName)[1];
            Logger::getLogger(__METHOD__)->info("Starting to migrate $MigrationName.");

            include $file;

            try {
                EventController::getEventDispatcher()->dispatch(new Event(), MigrationEvents::BEFORE_MIGRATE);
                $Migration = new $Class();

                if ($Migration->run()) {

                    $statement = $this->Database->prepare('INSERT INTO schema_migration (file, plugin) VALUES (:file, :plugin)');

                    $statement->execute([':file' => $MigrationName, ':plugin' => $Plugin]);

                    EventController::getEventDispatcher()->dispatch(new Event(), MigrationEvents::AFTER_MIGRATE);

                    Logger::getLogger(__METHOD__)->notice("Successfully Migrated $MigrationName");
                } else {

                    Logger::getLogger(__METHOD__)->error("Failed to migrate $MigrationName");
                    break;
                }
            } catch (\Exception $ex) {
                Logger::getLogger(__METHOD__)->error($ex);
                break;
            }
        }
    }

    /**
     * Create a new migration file.
     *
     * @param string $MigrationName the name of the migration, only Alpha Letters
     * @param string $Dir           The base directory to look for migrations. e.g Setting "plugin" will create one in "plugin/migrations"
     *
     * @since 0.0.8-beta.RC2
     */
    public static function create(string $MigrationName, string $Dir = __DIR__ . '/../'): void
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }

        $MigrationNameFiltered = Helper::filterAlphaNum($MigrationName);

        $Template = file_get_contents(__DIR__ . '/../migrations/template.php');

        $Skeleton = strtr($Template, [
            'MigrationName' => $MigrationNameFiltered,
            'RUNCODE;' => '$this->createTable("MyTable", array("col1", \crisp\core\Migrations::DB_VARCHAR));',
        ]);

        if (!file_exists("$Dir/migrations/")) {
            mkdir("$Dir/migrations/");
        }

        $written = file_put_contents("$Dir/migrations/" . time() . "_$MigrationNameFiltered.php", $Skeleton);

        if (!$written) {
            Logger::getLogger(__METHOD__)->error('Failed to write migration file, check permissions');
        } else {
            Logger::getLogger(__METHOD__)->notice('Migration File created!');
        }
    }

    /**
     * Create a new index for a table.
     *
     * @param  string      $Table     The name of the table
     * @param  string      $Column    The name of the column
     * @param  string      $Type      The type of the index
     * @param  string|null $IndexName The name of the index, Unused if PRIMARYKEY
     * @return bool
     * @throws \Exception  on PDO Error
     *
     * @since 0.0.8-beta.RC2
     */
    protected function addIndex(string $Table, string $Column, string $Type = self::DB_PRIMARYKEY, string $IndexName = null): bool
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        Logger::getLogger(__METHOD__)->debug("Adding index to table $Table...");
        if ($Type == self::DB_PRIMARYKEY) {
            $SQL = "ALTER TABLE $Table ADD $Type KEY ($Column);";
        } else {
            $SQL = "CREATE $Type INDEX $IndexName ON $Table ($Column);";
        }

        Logger::getLogger(__METHOD__)->debug($SQL);
        $statement = $this->Database->prepare($SQL);

        if ($statement->execute()) {
            Logger::getLogger(__METHOD__)->debug("Added index to table $Table...");

            return true;
        }
        Logger::getLogger(__METHOD__)->error("Failed to add index to table $Table...");
        Logger::getLogger(__METHOD__)->error('SQL ERROR', $statement->errorInfo());

        return false;
    }

    /**
     * Remove a column from a table.
     *
     * @param  string     $Table  The table name
     * @param  string     $Column The name of the column
     * @return bool
     * @throws \Exception on PDO Error
     *
     * @since 0.0.8-beta.RC3
     */
    protected function dropColumn(string $Table, string $Column): bool
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        Logger::getLogger(__METHOD__)->debug("Removing column from Table $Table...");
        $SQL = "ALTER TABLE $Table DROP COLUMN $Column";

        Logger::getLogger(__METHOD__)->debug($SQL);
        $statement = $this->Database->prepare($SQL);

        if ($statement->execute()) {
            Logger::getLogger(__METHOD__)->debug("Removed column from Table $Table...");

            return true;
        }
        Logger::getLogger(__METHOD__)->error("Failed to remove Column from table $Table...");
        Logger::getLogger(__METHOD__)->error('SQL ERROR', $statement->errorInfo());

        return false;
    }

    /**
     * Add a foreign key to a column.
     *
     * @param  string     $SourceTable     The table name to add the foreign key to
     * @param  string     $ReferenceTable  The Table to source the data from
     * @param  string     $SourceColumn    The column name to add the foreign key to
     * @param  string     $ReferenceColumn The column name to source the data from
     * @param  string     $ConstraintName  The name of the foreign_key
     * @return bool
     * @throws \Exception on PDO Error
     *
     * @since 11.2.0
     */
    protected function addForeignKey(string $SourceTable, string $ReferenceTable, string $SourceColumn, string $ReferenceColumn, string $ConstraintName): bool
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        Logger::getLogger(__METHOD__)->debug("Adding foreign key to Table $SourceTable...");
        $SQL = "ALTER TABLE $SourceTable ADD CONSTRAINT fk_$ConstraintName FOREIGN KEY ($SourceColumn) REFERENCES $ReferenceTable ($ReferenceColumn);";

        Logger::getLogger(__METHOD__)->debug($SQL);
        $statement = $this->Database->prepare($SQL);

        if ($statement->execute()) {
            Logger::getLogger(__METHOD__)->debug("Added foreign key to Table $SourceTable!");

            return true;
        }
        Logger::getLogger(__METHOD__)->error("Failed to add Foreign Key to table $SourceTable");
        Logger::getLogger(__METHOD__)->error('SQL ERROR', $statement->errorInfo());

        return false;
    }

    /**
     * Add a column to a table.
     *
     * @param  string     $Table  The table name
     * @param  array      $Column An array consisting of the column name, column type and additional info, e.g. array("ColName, self::DB_VARCHAR, "NOT NULL")
     * @return bool
     * @throws \Exception on PDO Error
     *
     * @since 0.0.8-beta.RC2
     */
    protected function addColumn(string $Table, array $Column): bool
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        Logger::getLogger(__METHOD__)->debug("Adding column to Table $Table...");
        $SQL = "ALTER TABLE $Table ADD COLUMN $Column[0] $Column[1] $Column[2];";

        Logger::getLogger(__METHOD__)->debug($SQL);
        $statement = $this->Database->prepare($SQL);

        if ($statement->execute()) {
            Logger::getLogger(__METHOD__)->debug("Added column to Table $Table!");

            return true;
        }
        Logger::getLogger(__METHOD__)->error("Failed to add Column to table $Table");
        Logger::getLogger(__METHOD__)->error('SQL ERROR', $statement->errorInfo());

        return false;
    }

    /**
     * Create a new table. This function accepts infinite parameters to add columns.
     *
     * @param  string     $Table      The table name
     * @param  mixed      ...$Columns An array consisting of the column name, column type and additional info, e.g. array("ColName, self::DB_VARCHAR, "NOT NULL")
     * @return bool
     * @throws \Exception
     *
     * @since 0.0.8-beta.RC2
     */
    protected function createTable(string $Table, ...$Columns): bool
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        Logger::getLogger(__METHOD__)->debug("Creating Table $Table...");
        $SQL = "CREATE TABLE IF NOT EXISTS $Table (";
        foreach ($Columns as $Key => $Column) {
            $Name = $Column[0];
            $Type = $Column[1];
            $Additional = $Column[2];
            if (str_contains($Additional, 'SERIAL')) {
                $SQL .= "$Name SERIAL,";
            } else {
                $SQL .= "$Name $Type $Additional,";
            }
            if ($Key == count($Columns) - 1) {
                $SQL = substr($SQL, 0, -1);
            }
        }
        $SQL .= ');';

        Logger::getLogger(__METHOD__)->debug($SQL);
        $statement = $this->Database->prepare($SQL);

        if ($statement->execute()) {
            Logger::getLogger(__METHOD__)->debug("Created Table $Table!");

            return true;
        }
        Logger::getLogger(__METHOD__)->error("Failed to create table $Table");
        Logger::getLogger(__METHOD__)->error('SQL ERROR', $statement->errorInfo());

        return false;
    }
}
