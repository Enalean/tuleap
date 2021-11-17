<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\DB\Compat\Legacy2018;

/**
 * @deprecated See \Tuleap\DB\DBFactory
 */
interface LegacyDataAccessInterface
{
    /**
     * Fetches a query resources and stores it in a local member
     * @param $sql string the database query to run
     * @deprecated
     * @return object MySQLDataAccessResultInterface
     *
     * @psalm-taint-sink sql $sql
     * @psalm-taint-sink sql $params
     */
    public function query($sql, $params = []);

    /**
     * Return ID generated from the previous INSERT operation.
     *
     * @deprecated
     *
     * @return false|int or 0 if the previous query does not generate an AUTO_INCREMENT value, or FALSE if no MySQL connection was established
     */
    public function lastInsertId();

    /**
     * Return number of rows affected by the last INSERT, UPDATE or DELETE.
     *
     * @deprecated
     *
     * @return int
     */
    public function affectedRows();

    /**
     * Returns any MySQL errors
     * @deprecated
     * @return string a MySQL error
     */
    public function isError();

    /**
     * @deprecated
     */
    public function getErrorMessage();

    /**
     * Quote variable to make safe
     * @see http://php.net/mysql-real-escape-string
     *
     * @deprecated
     *
     * @return string
     */
    public function quoteSmart($value, $params = []);

    /**
     * Quote schema name to make safe
     * @see http://php.net/mysql-real-escape-string
     *
     * @deprecated
     *
     * @return string
     */
    public function quoteSmartSchema($value, $params = []);

    /**
     * Safe implode function to use with SQL queries
     * @deprecated
     * @static
     */
    public function quoteSmartImplode($glue, $pieces, $params = []);

    /**
     * cast to int
     *
     * @deprecated
     */
    public function escapeInt($v, $null = CODENDI_DB_NOT_NULL): string;

    /**
     * @deprecated
     */
    public function escapeFloat($value);

    /**
     * Escape the ints, and implode them.
     *
     * @param array $ints
     *
     * @deprecated
     *
     * $return string
     */
    public function escapeIntImplode(array $ints);

    /**
     * Escape a value that will be used in a LIKE condition
     *
     * WARNING: This must be use only before quoteSmart otherwise you are still at risk of SQL injections
     *
     * Example escape chain:
     * $this->getDa()->quoteSmart($this->getDa()->escapeLikeValue($value));
     *
     * @deprecated
     *
     * @return string
     */
    public function escapeLikeValue($value);

    /**
     * @deprecated
     * @return string
     */
    public function quoteLikeValueSurround($value);

    /**
     * @deprecated
     * @return string
     */
    public function quoteLikeValueSuffix($value);

    /**
     * @deprecated
     * @return string
     */
    public function quoteLikeValuePrefix($value);

    /**
     * Retrieves the number of rows from a result set.
     *
     * @param resource $result The result resource that is being evaluated. This result comes from a call to query().
     *
     * @deprecated
     *
     * @return int The number of rows in a result set on success, or FALSE on failure.
     */
    public function numRows($result);

    /**
     * Fetch a result row as an associative array
     *
     * @param resource $result The result resource that is being evaluated. This result comes from a call to query().
     *
     * @deprecated
     *
     * @return array Returns an associative array of strings that corresponds to the fetched row, or FALSE if there are no more rows.
     */
    public function fetch($result);

    /**
     * Backward compatibility with database.php
     *
     * @deprecated since version 4.0
     * @param type $result
     *
     * @return type
     * @psalm-taint-source ldap
     */
    public function fetchArray($result);

    /**
     * Move internal result pointer
     *
     * @param resource $result The result resource that is being evaluated. This result comes from a call to query().
     * @param int $row_number The desired row number of the new result pointer.
     *
     * @deprecated
     *
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function dataSeek($result, $row_number);

    /**
     * Start a sql transaction
     * @deprecated
     */
    public function startTransaction();

    /**
     * Rollback a sql transaction
     * @deprecated
     */
    public function rollback();

    /**
     * Commit a sql transaction
     * @deprecated
     */
    public function commit();
}
