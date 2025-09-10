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

if (!defined('CRISP_HOOKED')) {
    echo 'Illegal File access';
    exit;
}

class createcrons extends \crisp\core\Migrations
{
    public function run()
    {
        if (Logger::isTraceEnabled()) {
            Logger::getLogger(__METHOD__)->log(Logger::LOG_LEVEL_TRACE, 'Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        }
        try {
            $this->begin();

            $this->createTable(
                'Cron',
                ['ID', $this::DB_INTEGER, 'NOT NULL SERIAL'],
                ['Type', $this::DB_VARCHAR, 'NOT NULL'],
                ['ScheduledAt', $this::DB_TIMESTAMP, 'NOT NULL DEFAULT CURRENT_TIMESTAMP'],
                ['CreatedAt', $this::DB_TIMESTAMP, 'DEFAULT NULL'],
                ['FinishedAt', $this::DB_TIMESTAMP, 'DEFAULT NULL'],
                ['UpdatedAt', $this::DB_TIMESTAMP, 'DEFAULT NULL'],
                ['StartedAt', $this::DB_TIMESTAMP, 'DEFAULT NULL'],
                ['Finished', $this::DB_BOOL, 'NOT NULL DEFAULT 0'],
                ['Started', $this::DB_BOOL, 'NOT NULL DEFAULT 0'],
                ['Canceled', $this::DB_BOOL, 'NOT NULL DEFAULT 0'],
                ['Failed', $this::DB_BOOL, 'NOT NULL DEFAULT 0'],
                ['Data', $this::DB_LONGTEXT, 'DEFAULT NULL'],
                ['Logger', $this::DB_LONGTEXT, 'DEFAULT NULL'],
                ['Interval', $this::DB_VARCHAR, "DEFAULT '5 MINUTE'"],
                ['Plugin', $this::DB_VARCHAR, 'DEFAULT NULL']
            );

            return $this->end();
        } catch (\Exception $ex) {
            echo $ex->getMessage() . PHP_EOL;
            $this->rollback();

            return false;
        }
    }
}
