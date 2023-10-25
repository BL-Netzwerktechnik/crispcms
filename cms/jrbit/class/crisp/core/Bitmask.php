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

enum Bitmask: int
{
    case NONE = 0x0;
    case RESERVED = 0x1;
    case INTERFACE_NOT_FOUND = 0x2;
    case GENERATE_FAILED = 0x4;
    case RESERVED_4 = 0x8;
    case QUERY_FAILED = 0x10;
    case METHOD_DEPRECATED = 0x20;
    case INTERFACE_DEPRECATED = 0x40;
    case VERSION_DEPRECATED = 0x80;
    case REQUEST_SUCCESS = 0x100; // Request went through just fine. Used in new versions
    case VERSION_NOT_FOUND = 0x200;
    case RESERVED_6 = 0x400;
    case RESERVED_7 = 0x800;
    case RESERVED_8 = 0x1000;
    case METHOD_NOT_ALLOWED = 0x2000; // Send this along with a 405
    case NOT_IMPLEMENTED = 0x4000; // Send this along with a 501
    case MISSING_PARAMETER = 0x8000;
    case INVALID_PARAMETER = 0x10000;
    case GENERIC_ERROR = 0x20000;
    case LICENSE_INVALID = 0x40000;
    case RESERVED_1 = 0x80000;
    case POSTGRES_CONN_ERROR = 0x100000;
    case RESERVED_2 = 0x200000;
    case ELASTIC_CONN_ERROR = 0x400000;
    case ELASTIC_QUERY_MALFORMED = 0x800000;
    case REDIS_CONN_ERROR = 0x1000000;
    case RESERVED_3 = 0x2000000;
    case TWIG_ERROR = 0x4000000;
    case RESERVED_5 = 0x8000000;
    case THEME_MISSING_INCLUDES = 0x10000000;
    case MISSING_PERMISSIONS = 0x20000000;
}
