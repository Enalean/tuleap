<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */
if (! defined('CODENDI_DB_NULL')) {
    define('CODENDI_DB_NULL', 0);
}
if (! defined('CODENDI_DB_NOT_NULL')) {
    define('CODENDI_DB_NOT_NULL', 1);
}

/**
 * @deprecated
 * @psalm-taint-sink sql $sql
 */
function db_query($sql)
{
    /** @psalm-suppress DeprecatedFunction */
    return db_query_params($sql, []);
}

/**
 * @deprecated
 *
 * @psalm-taint-sink sql $sql
 * @psalm-taint-sink sql $params
 */
function db_query_params($sql, $params)
{
    $dar = CodendiDataAccess::instance()->query($sql, $params);
    $GLOBALS['db_qhandle'] = $dar->getResult();
    /** @psalm-suppress DeprecatedFunction */
    if (db_numrows($GLOBALS['db_qhandle'])) {
        /** @psalm-suppress DeprecatedFunction */
        db_reset_result($GLOBALS['db_qhandle']);
    }
    return $GLOBALS['db_qhandle'];
}

/**
 * @deprecated
 */
function db_numrows($qhandle)
{
    // return only if qhandle exists, otherwise 0
    if ($qhandle) {
        return @CodendiDataAccess::instance()->numRows($qhandle);
    }
    return 0;
}

/**
 * @deprecated
 */
function db_free_result($qhandle)
{
    $qhandle->freeMemory();
}

/**
 * @deprecated
 */
function db_result($qhandle, $row, $field)
{
    $qhandle->seek($row);
    $row = $qhandle->current();
    if ($field === null) {
        $field = 0;
    }
    if (isset($row[$field])) {
        return $row[$field];
    }
    return false;
}

/**
 * @deprecated
 */
function db_numfields($lhandle)
{
    return $lhandle->columnCount();
}

/**
 * @deprecated
 */
function db_affected_rows($qhandle)
{
    return @CodendiDataAccess::instance()->affectedRows();
}

/**
 * @deprecated
 */
function db_fetch_array($qhandle = 0)
{
    if ($qhandle) {
        return CodendiDataAccess::instance()->fetchArray($qhandle);
    } else {
        if ($GLOBALS['db_qhandle']) {
            return CodendiDataAccess::instance()->fetchArray($GLOBALS['db_qhandle']);
        } else {
            return ([]);
        }
    }
}

/**
 * @deprecated
 */
function db_insertid($qhandle)
{
    return CodendiDataAccess::instance()->lastInsertId();
}

/**
 * Display real error only if we are in Debug mode
 *
 * @deprecated
 *
 * @return String
 */
function db_error()
{
    return CodendiDataAccess::instance()->isError();
}

/**
 *  db_reset_result() - Reset a result set.
 *
 *  Reset is useful for db_fetch_array sometimes you need to start over
 *
 *  @param        string    Query result set handle
 *  @param        int        Row number
 *
 * @deprecated
 */
function db_reset_result($qhandle, $row = 0)
{
    return CodendiDataAccess::instance()->dataSeek($qhandle, $row);
}

/**
 * @deprecated
 */
function db_escape_string($string, $qhandle = false)
{
    return substr(CodendiDataAccess::instance()->quoteSmart($string), 1, -1);
}

/**
 * Alias for db_escape_string.
 * @deprecated
 */
function db_es($string, $qhandle = false)
{
    /** @psalm-suppress DeprecatedFunction */
    return db_escape_string($string, $qhandle);
}

/**
 * Escape value as a valid decimal integer.
 *
 * If input is not a valid decimal integer, return '0'.
 * If CODENDI_DB_NULL is used, empty string '' as $val returns 'NULL' string.
 * This last form is useful when the corresponding field is defined as INT or
 * NULL in SQL.
 *
 * @see http://php.net/language.types.integer
 * @param  mixed $val a value to escape
 * @param  int   $null CODENDI_DB_NOT_NULL or CODENDI_DB_NULL
 *
 * @deprecated
 *
 * @return string Decimal integer encoded as a string
 */
function db_escape_int($val, $null = CODENDI_DB_NOT_NULL)
{
    $match = [];
    if ($null === CODENDI_DB_NULL && $val === '') {
        return 'NULL';
    }
    if (preg_match('/^([+-]?[1-9][0-9]*|[+-]?0)$/', $val, $match)) {
        return $match[1];
    }
    return '0';
}

/**
 * Alias for db_escape_int
 *
 * @param mixed $val a value to escape
 * @param  int   $null CODENDI_DB_NOT_NULL or CODENDI_DB_NULL
 * @deprecated
 * @return string Decimal integer encoded as a string
 */
function db_ei($val, $null = CODENDI_DB_NOT_NULL)
{
    /** @psalm-suppress DeprecatedFunction */
    return db_escape_int($val, $null);
}

/**
 * @deprecated
 */
function db_ei_implode($val)
{
    return implode(',', array_map('db_ei', $val));
}
