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

class createsessiontable extends \crisp\core\Migrations {

    public function run() {
        try {
            $this->begin();
            $this->createTable("sessions",
                    array("id", $this::DB_INTEGER, "NOT NULL SERIAL"),
                    array("token", $this::DB_VARCHAR, "NOT NULL"),
                    array('"user"', $this::DB_VARCHAR, "NOT NULL"),
                    array("Createdat", $this::DB_TIMESTAMP, "NOT NULL DEFAULT CURRENT_TIMESTAMP"),
                    array("identifier", $this::DB_VARCHAR, "NOT NULL DEFAULT 'login'"),
                    array("oidc_token", $this::DB_VARCHAR, "NOT NULL")
            );
            $this->addIndex("sessions", "token", $this::DB_PRIMARYKEY);
            return $this->end();
        } catch (\Exception $ex) {
            echo $ex->getMessage() . PHP_EOL;
            $this->rollback();
            return false;
        }
    }

}
