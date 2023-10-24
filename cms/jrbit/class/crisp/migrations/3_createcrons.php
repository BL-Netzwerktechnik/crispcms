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


namespace crisp\migrations;

use crisp\core\Logger;

if(!defined('CRISP_HOOKED')){
    echo 'Illegal File access';
    exit;
}

class createcrons extends \crisp\core\Migrations {

    public function run() {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        try {
            $this->begin();



            $this->createTable("Cron",
                    array("ID", $this::DB_INTEGER, "NOT NULL SERIAL"),
                    array("Type", $this::DB_VARCHAR, "NOT NULL"),
                    array("ScheduledAt", $this::DB_TIMESTAMP, "NOT NULL DEFAULT CURRENT_TIMESTAMP"),
                    array("CreatedAt", $this::DB_TIMESTAMP, "DEFAULT NULL"),
                    array("FinishedAt", $this::DB_TIMESTAMP, "DEFAULT NULL"),
                    array("UpdatedAt", $this::DB_TIMESTAMP, "DEFAULT NULL"),
                    array("StartedAt", $this::DB_TIMESTAMP, "DEFAULT NULL"),
                    array("Finished", $this::DB_BOOL, "NOT NULL DEFAULT 0"),
                    array("Started", $this::DB_BOOL, "NOT NULL DEFAULT 0"),
                    array("Canceled", $this::DB_BOOL, "NOT NULL DEFAULT 0"),
                    array("Failed", $this::DB_BOOL, "NOT NULL DEFAULT 0"),
                    array("Data", $this::DB_LONGTEXT, "DEFAULT NULL"),
                    array("Logger", $this::DB_LONGTEXT, "DEFAULT NULL"),
                    array("Interval", $this::DB_VARCHAR, "DEFAULT '5 MINUTE'"),
                    array("Plugin", $this::DB_VARCHAR, "DEFAULT NULL")
            );

            return $this->end();
        } catch (\Exception $ex) {
            echo $ex->getMessage() . PHP_EOL;
            $this->rollback();
            return false;
        }
    }

}
