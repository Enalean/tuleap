<?php
/**
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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

use ForgeConfig;
use Tuleap\DB\DBConnection;

/**
 * @deprecated
 */
final class CompatPDODataAccess implements LegacyDataAccessInterface
{
    /**
     * @var DBConnection
     */
    private $db_connection;
    /**
     * @var \PDOStatement
     */
    private $latest_statement;

    public function __construct(DBConnection $db_connection)
    {
        $this->db_connection = $db_connection;
    }

    /**
     * Fetches a query resources and stores it in a local member
     * @param $sql string the database query to run
     * @deprecated
     * @return object MySQLDataAccessResultInterface
     */
    public function query($sql, $params = array())
    {
        if (! empty($params)) {
            $args = [];
            $i    = 1;
            foreach ($params as $param) {
                $args[] = '$' . $i;
            }
            $sql = str_replace($args, $params, $sql);
        }

        try {
            $this->latest_statement = $this->db_connection->getDB()->query($sql);
        } catch (\PDOException $ex) {
            $this->latest_statement = null;
            if ($ex->getCode() == 2006) {
                throw new \DataAccessException('Unable to access the database . Please contact your administrator.');
            }
        }

        try {
            $this->db_connection->getDB()->getPdo()->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, true);
            $data_access_result = new CompatPDODataAccessResult($this->latest_statement);
        } finally {
            $this->db_connection->getDB()->getPdo()->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
        }
        return $data_access_result;
    }

    /**
     * Return ID generated from the previous INSERT operation.
     *
     * @deprecated
     *
     * @return false|int or 0 if the previous query does not generate an AUTO_INCREMENT value, or FALSE if no MySQL connection was established
     */
    public function lastInsertId()
    {
        try {
            return (int) $this->db_connection->getDB()->lastInsertId();
        } catch (\PDOException $ex) {
            return false;
        }
    }

    /**
     * Return number of rows affected by the last INSERT, UPDATE or DELETE.
     *
     * @deprecated
     *
     * @return int
     */
    public function affectedRows()
    {
        if ($this->latest_statement === null) {
            return -1;
        }
        try {
            return $this->latest_statement->rowCount();
        } catch (\PDOException $ex) {
            return -1;
        }
    }

    /**
     * Returns any MySQL errors
     * @deprecated
     * @return string a MySQL error
     */
    public function isError()
    {
        $error_info = [];
        if ($this->latest_statement !== null) {
            $error_info = $this->latest_statement->errorInfo();
        }

        if (! isset($error_info[0]) || $error_info[0] === '00000') {
            $error_info = $this->db_connection->getDB()->getPdo()->errorInfo();
        }

        $has_error = isset($error_info[0]) && $error_info[0] !== '00000';
        if (! $has_error) {
            return '';
        }
        if (! ForgeConfig::get('DEBUG_MODE')) {
            return 'DB error';
        }
        return $error_info[2];
    }

    /**
     * @deprecated
     */
    public function getErrorMessage()
    {
        $error_info = [];
        if ($this->latest_statement !== null) {
            $error_info = $this->latest_statement->errorInfo();
        }

        if (! isset($error_info[0]) || $error_info[0] === '00000') {
            $error_info = $this->db_connection->getDB()->getPdo()->errorInfo();
        }

        return $error_info[2] . ' - ' . $error_info[1];
    }

    /**
     * Quote variable to make safe
     * @see http://php.net/mysql-real-escape-string
     *
     * @deprecated
     *
     * @return string
     */
    public function quoteSmart($value, $params = array())
    {
        return $this->db_connection->getDB()->quote((string) $value);
    }

    /**
     * Quote schema name to make safe
     * @see http://php.net/mysql-real-escape-string
     *
     * @deprecated
     *
     * @return string
     */
    public function quoteSmartSchema($value, $params = array())
    {
        return $this->db_connection->getDB()->escapeIdentifier((string) $value);
    }

    /**
     * Safe implode function to use with SQL queries
     * @deprecated
     * @static
     */
    public function quoteSmartImplode($glue, $pieces, $params = array())
    {
        $str         = '';
        $after_first = false;
        foreach ($pieces as $piece) {
            if ($after_first) {
                $str .= $glue;
            }
            $str        .= $this->quoteSmart($piece, $params);
            $after_first = true;
        }
        return $str;
    }

    /**
     * cast to int
     *
     * @deprecated
     *
     * @return int
     */
    public function escapeInt($v, $null = CODENDI_DB_NOT_NULL)
    {
        if ($null === CODENDI_DB_NULL && $v === '') {
            return 'NULL';
        }
        $m = [];
        if (preg_match('/^([+-]?[1-9][0-9]*|[+-]?0)$/', $v, $m)) {
            return $m[1];
        }
        return '0';
    }

    /**
     * @deprecated
     */
    public function escapeFloat($value)
    {
        if ($value === '') {
            return 'NULL';
        }

        return (float) $value;
    }

    /**
     * Escape the ints, and implode them.
     *
     * @param array $ints
     *
     * @deprecated
     *
     * $return string
     */
    public function escapeIntImplode(array $ints)
    {
        return implode(',', array_map(array($this, 'escapeInt'), $ints));
    }

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
    public function escapeLikeValue($value)
    {
        $value = $value ?? '';
        return $this->db_connection->getDB()->escapeLikeValue($value);
    }

    /**
     * @deprecated
     * @return string
     */
    public function quoteLikeValueSurround($value)
    {
        return $this->quoteSmart('%' . $this->escapeLikeValue($value) . '%');
    }

    /**
     * @deprecated
     * @return string
     */
    public function quoteLikeValueSuffix($value)
    {
        return $this->quoteSmart($this->escapeLikeValue($value) . '%');
    }

    /**
     * @deprecated
     * @return string
     */
    public function quoteLikeValuePrefix($value)
    {
        return $this->quoteSmart('%' . $this->escapeLikeValue($value));
    }

    /**
     * Retrieves the number of rows from a result set.
     *
     * @param CompatPDODataAccessResult|false $result The result resource that is being evaluated. This result comes from a call to query().
     *
     * @deprecated
     *
     * @return int The number of rows in a result set on success, or FALSE on failure.
     */
    public function numRows($result)
    {
        if (! $result) {
            return false;
        }

        return $result->rowCount();
    }

    /**
     * Fetch a result row as an associative array
     *
     * @param CompatPDODataAccessResult|false $result The result resource that is being evaluated. This result comes from a call to query().
     *
     * @deprecated
     *
     * @return array Returns an associative array of strings that corresponds to the fetched row, or FALSE if there are no more rows.
     */
    public function fetch($result)
    {
        if (! $result) {
            return false;
        }
        if (! $result->valid()) {
            return false;
        }
        $value = $result->current();
        $result->next();
        return $value;
    }

    /**
     * Backward compatibility with database.php
     *
     * @deprecated since version 4.0
     * @param type $result
     *
     * @return type
     */
    public function fetchArray($result)
    {
        return $this->fetch($result);
    }

    /**
     * Move internal result pointer
     *
     * @param CompatPDODataAccessResult|false $result The result resource that is being evaluated. This result comes from a call to query().
     * @param int $row_number The desired row number of the new result pointer.
     *
     * @deprecated
     *
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function dataSeek($result, $row_number)
    {
        if (! $result) {
            return false;
        }
        $result->seek($row_number);
        return $result->valid();
    }

    /**
     * Start a sql transaction
     * @deprecated
     */
    public function startTransaction()
    {
        if ($this->db_connection->getDB()->getPdo()->inTransaction()) {
            $this->commit();
        }
        try {
            return $this->db_connection->getDB()->beginTransaction();
        } catch (\PDOException $ex) {
            return false;
        }
    }

    /**
     * Rollback a sql transaction
     * @deprecated
     */
    public function rollback()
    {
        if (! $this->db_connection->getDB()->getPdo()->inTransaction()) {
            return true;
        }
        try {
            return $this->db_connection->getDB()->rollBack();
        } catch (\PDOException $ex) {
            return false;
        }
    }

    /**
     * Commit a sql transaction
     * @deprecated
     */
    public function commit()
    {
        if (! $this->db_connection->getDB()->getPdo()->inTransaction()) {
            return true;
        }
        try {
            return $this->db_connection->getDB()->commit();
        } catch (\PDOException $ex) {
            return false;
        }
    }
}
