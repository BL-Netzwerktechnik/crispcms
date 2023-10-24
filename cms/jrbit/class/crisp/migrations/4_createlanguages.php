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

class createlanguages extends \crisp\core\Migrations {

    public function run() {
        Logger::getLogger(__METHOD__)->debug("Called", debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]);
        try {
            $this->begin();


            $this->createTable("Languages",
                    array("ID", $this::DB_INTEGER, "NOT NULL SERIAL"),
                    array("Name", $this::DB_VARCHAR, "NOT NULL"),
                    array("Code", $this::DB_VARCHAR, "NOT NULL"),
                    array("NativeName", $this::DB_VARCHAR, "NOT NULL"),
                    array("Flag", $this::DB_VARCHAR, "NOT NULL"),
                    array("Enabled", $this::DB_BOOL, "NOT NULL DEFAULT 0"),
            );


            return $this->end();
        } catch (\Exception $ex) {
            echo $ex->getMessage() . PHP_EOL;
            $this->rollback();
            return false;
        }
    }

}
