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
use Exception;
use PDO;

/**
 * Crisp DB Migration Class
 * @since 0.0.8-beta.RC1
 */
class Migrations
{

    /**
     * The PDO Database
     * @var PDO
     */
    protected PDO $Database;

    /* Data types */

    const DB_VARCHAR = "varchar(255)";
    const DB_TEXT = "text";
    const DB_INTEGER = "integer";
    const DB_TIMESTAMP = "timestamp";
    const DB_BOOL = "smallint";
    const DB_LONGTEXT = "text";
    const DB_BIGINT = "bigint";

    /* Keys */
    const DB_PRIMARYKEY = "PRIMARY";
    const DB_UNIQUEKEY = "UNIQUE";

    public function __construct()
    {
        $DB = new Postgres();
        $this->Database = $DB->getDBConnector();
    }

    /**
     * Begin a MySQL Transaction
     * @return boolean
     * @since 0.0.8-beta.RC2
     */
    protected function begin(): bool
    {
        echo "Enabling Transactions..." . PHP_EOL;
        if ($this->Database->beginTransaction()) {
            echo "Enabled Transactions!" . PHP_EOL;
            return true;
        }
        echo "Failed to enable transactions..." . PHP_EOL;
        return false;
    }

    /**
     * Rollback a MySQL Transaction
     * @return boolean
     * @since 0.0.8-beta.RC2
     */
    protected function rollback(): bool
    {
        echo "Rolling back..." . PHP_EOL;
        if ($this->Database->rollBack()) {
            echo "Rolled back!" . PHP_EOL;
            return true;
        }
        echo "Failed to rollback..." . PHP_EOL;
        return false;
    }

    /**
     * End/commit a MySQL Transaction
     * @return boolean
     * @since 0.0.8-beta.RC2
     */
    protected function end(): bool
    {
        echo "committing changes..." . PHP_EOL;
        if ($this->Database->commit()) {
            echo "Changes committed!" . PHP_EOL;
            return true;
        }
        echo "Failed to commit changes!" . PHP_EOL;
        return false;
    }

    /**
     * Check if a migration is already installed
     * @param string $file The migration filename to check. Don't use the extension in the filename.
     * @see basename
     * @since 0.0.8-beta.RC2
     * @return boolean
     */
    public function isMigrated(string $file): bool
    {

        try {
            $statement = $this->Database->prepare("SELECT * FROM schema_migration WHERE file =:file");

            $statement->execute(array(":file" => $file));
            return $statement->rowCount() > 0;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Begin the migration of the database
     * @param string $Dir The directory base to look for migrations. e.g Setting "plugin" will look in "plugin/migrations"
     * @param string|null $Plugin If the migration has been done for a plugin, this is the name
     * @return void
     * @since 0.0.8-beta.RC2
     */
    public function migrate(string $Dir = __DIR__ . "/../", ?string $Plugin = null): void
    {
        if (PHP_SAPI === "cli") {
            echo "Starting Migration..." . PHP_EOL;
        }
        if (!file_exists("$Dir/migrations/")) {
            if (PHP_SAPI === "cli") {
                echo "No migrations needed!" . PHP_EOL;
            }
            return;
        }
        $files = glob("$Dir/migrations/*.{php}", GLOB_BRACE);

        natsort($files);

        foreach ($files as $file) {
            if (basename($file) == "template.php") {
                continue;
            }

            $MigrationName = substr(basename($file), 0, -4);

            if ($MigrationName !== "0_createmigration" && $this->isMigrated($MigrationName)) {
                if (PHP_SAPI === "cli") {
                    echo "$MigrationName is already migrated, skipping!" . PHP_EOL;
                }
                continue;
            }

            $Class = "\crisp\migrations\\" . explode("_", $MigrationName)[1];

            include $file;

            try {
                $Migration = new $Class();

                if ($Migration->run()) {

                    $statement = $this->Database->prepare("INSERT INTO schema_migration (file, plugin) VALUES (:file, :plugin)");

                    $statement->execute(array(":file" => $MigrationName, ":plugin" => $Plugin));

                    if (php_sapi_name() == "cli") {
                        echo "Migrated $MigrationName" . PHP_EOL;
                    }
                } else {

                    if (php_sapi_name() == "cli") {
                        echo "Failed to migrate $MigrationName" . PHP_EOL;
                    }
                }
            } catch (\Exception $ex) {
                if (php_sapi_name() == "cli") {
                    echo "Failed to migrate $MigrationName" . PHP_EOL . $ex->getMessage() . PHP_EOL;
                }
            }
        }
    }

    /**
     * Create a new migration file.
     * @param string $MigrationName The name of the migration, only Alpha Letters.
     * @param string $Dir The base directory to look for migrations. e.g Setting "plugin" will create one in "plugin/migrations"
     * @return void
     * @since 0.0.8-beta.RC2
     */
    public static function create(string $MigrationName, string $Dir = __DIR__ . "/../"): void
    {

        $MigrationNameFiltered = Helper::filterAlphaNum($MigrationName);

        $Template = file_get_contents(__DIR__ . "/../migrations/template.php");

        $Skeleton = strtr($Template, array(
            "MigrationName" => $MigrationNameFiltered,
            "RUNCODE;" => '$this->createTable("MyTable", array("col1", \crisp\core\Migrations::DB_VARCHAR));'
        ));

        if (!file_exists("$Dir/migrations/")) {
            mkdir("$Dir/migrations/");
        }

        $written = file_put_contents("$Dir/migrations/" . time() . "_$MigrationNameFiltered.php", $Skeleton);

        if (!$written) {
            echo "Failed to write migration file, check permissions!" . PHP_EOL;
        } else {
            echo "Migration file written!" . PHP_EOL;
        }
    }

    /**
     * Create a new index for a table
     * @param string $Table The name of the table
     * @param string $Column The name of the column
     * @param string $Type The type of the index
     * @param string|null $IndexName The name of the index, Unused if PRIMARYKEY
     * @return boolean
     * @throws Exception on PDO Error
     * @since 0.0.8-beta.RC2
     */
    protected function addIndex(string $Table, string $Column, string $Type = self::DB_PRIMARYKEY, string $IndexName = null): bool
    {
        echo "Adding index to table $Table..." . PHP_EOL;
        if ($Type == self::DB_PRIMARYKEY) {
            $SQL = "ALTER TABLE $Table ADD $Type KEY ($Column);";
        } else {
            $SQL = "CREATE $Type INDEX $IndexName ON $Table ($Column);";
        }

        $statement = $this->Database->prepare($SQL);

        if ($statement->execute()) {
            echo "Added Index to Table $Table!" . PHP_EOL;
            return true;
        }
        echo "Failed to add Index to Table $Table!" . PHP_EOL;
        throw new Exception($statement->errorInfo());
    }

    /**
     * @param $PluginName
     * @return bool
     */
    public function deleteByTheme($PluginName): bool
    {
        $statement = $this->Database->prepare("DELETE FROM schema_migration WHERE plugin = :Plugin");

        return $statement->execute(array(":Plugin" => $PluginName));
    }

    /**
     * Remove a column from a table
     * @param string $Table The table name
     * @param string $Column The name of the column
     * @return boolean
     * @throws Exception on PDO Error
     * @since 0.0.8-beta.RC3
     */
    protected function dropColumn(string $Table, string $Column): bool
    {
        echo "Removing column from Table $Table..." . PHP_EOL;
        $SQL = "ALTER TABLE $Table DROP COLUMN $Column";

        $statement = $this->Database->prepare($SQL);

        if ($statement->execute()) {
            echo "Removed Column from Table $Table!" . PHP_EOL;
            return true;
        }
        echo "Failed to remove Column from Table $Table!" . PHP_EOL;
        throw new Exception($statement->errorInfo());
    }
    

    /**
     * Add a foreign key to a column
     * @param string $SourceTable The table name to add the foreign key to
     * @param string $ReferenceTable The Table to source the data from
     * @param string $SourceColumn The column name to add the foreign key to
     * @param string $ReferenceColumn The column name to source the data from
     * @param string $ConstraintName The name of the foreign_key
     * @return boolean
     * @throws Exception on PDO Error
     * @since 11.2.0
     */
    protected function addForeignKey(string $SourceTable, string $ReferenceTable, string $SourceColumn,  string $ReferenceColumn, string $ConstraintName): bool
    {
        echo "Adding foreign key to Table $SourceTable..." . PHP_EOL;
        $SQL = "ALTER TABLE $SourceTable ADD CONSTRAINT fk_$ConstraintName FOREIGN KEY ($SourceColumn) REFERENCES $ReferenceTable ($ReferenceColumn);";

        $statement = $this->Database->prepare($SQL);

        if ($statement->execute()) {
            echo "Added Foreign Key to Table $SourceTable!" . PHP_EOL;
            return true;
        }
        echo "Failed to add Foreign Key to Table $SourceTable!" . PHP_EOL;
        throw new Exception($statement->errorInfo());
    }

    /**
     * Add a column to a table
     * @param string $Table The table name
     * @param array $Column An array consisting of the column name, column type and additional info, e.g. array("ColName, self::DB_VARCHAR, "NOT NULL")
     * @return boolean
     * @throws Exception on PDO Error
     * @since 0.0.8-beta.RC2
     */
    protected function addColumn(string $Table, array $Column): bool
    {
        echo "Adding column to Table $Table..." . PHP_EOL;
        $SQL = "ALTER TABLE $Table ADD COLUMN $Column[0] $Column[1] $Column[2];";

        $statement = $this->Database->prepare($SQL);

        if ($statement->execute()) {
            echo "Added Column to Table $Table!" . PHP_EOL;
            return true;
        }
        echo "Failed to add Column to Table $Table!" . PHP_EOL;
        throw new Exception($statement->errorInfo());
    }

    /**
     * Create a new table. This function accepts infinite parameters to add columns
     * @param string $Table The table name
     * @param mixed ...$Columns An array consisting of the column name, column type and additional info, e.g. array("ColName, self::DB_VARCHAR, "NOT NULL")
     * @return boolean
     * @throws Exception
     * @since 0.0.8-beta.RC2
     */
    protected function createTable(string $Table, ...$Columns): bool
    {
        echo "Creating Table $Table..." . PHP_EOL;
        $SQL = "CREATE TABLE IF NOT EXISTS $Table (";
        foreach ($Columns as $Key => $Column) {
            $Name = $Column[0];
            $Type = $Column[1];
            $Additional = $Column[2];
            if (str_contains($Additional, "SERIAL")) {
                $SQL .= "$Name SERIAL,";
            } else {
                $SQL .= "$Name $Type $Additional,";
            }
            if ($Key == count($Columns) - 1) {
                $SQL = substr($SQL, 0, -1);
            }
        }
        $SQL .= ");";


        $statement = $this->Database->prepare($SQL);

        if ($statement->execute()) {
            echo "Creating Table $Table!" . PHP_EOL;
            return true;
        }
        echo "Failed to create Table $Table!" . PHP_EOL;
        throw new Exception($statement->errorInfo());
    }

}
