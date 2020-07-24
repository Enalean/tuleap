<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use Tuleap\DB\DBFactory;

require_once __DIR__ . '/../DB/common.php';

/**
 * @deprecated
 */
class DB_mysql_pdo extends DB_common // @codingStandardsIgnoreLine
{
    /**
     * @var \ParagonIE\EasyDB\EasyDB
     */
    private $connection;
    /**
     * @var int
     */
    private $num_affected_rows = 0;

    public function __construct()
    {
        parent::__construct();
        $this->phptype = 'mysql';
        $this->dbsyntax = 'mysql';
        $this->features = [
            'prepare' => false,
            'pconnect' => true,
            'transactions' => true,
            'limit' => 'alter'
        ];
        $this->errorcode_map = [
            1004 => DB_ERROR_CANNOT_CREATE,
            1005 => DB_ERROR_CANNOT_CREATE,
            1006 => DB_ERROR_CANNOT_CREATE,
            1007 => DB_ERROR_ALREADY_EXISTS,
            1008 => DB_ERROR_CANNOT_DROP,
            1022 => DB_ERROR_ALREADY_EXISTS,
            1046 => DB_ERROR_NODBSELECTED,
            1050 => DB_ERROR_ALREADY_EXISTS,
            1051 => DB_ERROR_NOSUCHTABLE,
            1054 => DB_ERROR_NOSUCHFIELD,
            1062 => DB_ERROR_ALREADY_EXISTS,
            1064 => DB_ERROR_SYNTAX,
            1100 => DB_ERROR_NOT_LOCKED,
            1136 => DB_ERROR_VALUE_COUNT_ON_ROW,
            1146 => DB_ERROR_NOSUCHTABLE,
            1048 => DB_ERROR_CONSTRAINT,
            1216 => DB_ERROR_CONSTRAINT
        ];
    }

    public function connect()
    {
        $this->connection = DBFactory::getMainTuleapDBConnection()->getDB();
        return DB_OK;
    }

    public function disconnect()
    {
        return true;
    }

    public function simpleQuery($query)
    {
        $this->last_query        = $query;
        $this->num_affected_rows = 0;
        $query                    = $this->modifyQuery($query);

        try {
            $this->connection->getPdo()->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, true);
            if (DB::isManip($this->last_query)) {
                $this->num_affected_rows = $this->connection->safeQuery(
                    $query,
                    [],
                    \ParagonIE\EasyDB\EasyDB::DEFAULT_FETCH_STYLE,
                    true
                );
                return DB_OK;
            }
            $rows = $this->connection->run($query);
        } catch (PDOException $ex) {
            $this->raiseCompatError($ex);
        } finally {
            $this->connection->getPdo()->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
        }

        if ($rows === null) {
            return DB_OK;
        }

        return new ArrayIterator($rows);
    }

    protected function modifyQuery($query)
    {
        if ($this->options['portability'] & DB_PORTABILITY_DELETE_COUNT) {
            // "DELETE FROM table" gives 0 affected rows in MySQL.
            // This little hack lets you know how many rows were deleted.
            if (preg_match('/^\s*DELETE\s+FROM\s+(\S+)\s*$/i', $query)) {
                $query = preg_replace(
                    '/^\s*DELETE\s+FROM\s+(\S+)\s*$/',
                    'DELETE FROM \1 WHERE 1=1',
                    $query
                );
            }
        }
        return $query;
    }

    public function modifyLimitQuery($query, $from, $count)
    {
        if (DB::isManip($query)) {
            return $query . " LIMIT $count";
        }
        return $query . " LIMIT $from, $count";
    }

    private function raiseCompatError(PDOException $ex)
    {
        $this->raiseError($ex->getCode(), null, null, null, $ex->getMessage());
    }

    public function nextResult($result)
    {
        return false;
    }

    public function fetchInto($result, &$arr, $fetchmode, $rownum = null)
    {
        if ($rownum !== null) {
            $result->seek($rownum);
        }
        $row = $result->current();
        $result->next();
        if ($fetchmode & DB_FETCHMODE_ASSOC) {
            $arr = $row;
            if ($this->options['portability'] & DB_PORTABILITY_LOWERCASE && $arr) {
                $arr = array_change_key_case($arr, CASE_LOWER);
            }
        } else {
            $arr = array_values($row);
        }
        if (! $arr) {
            return null;
        }
        if ($this->options['portability'] & DB_PORTABILITY_RTRIM) {
            /*
             * Even though this DBMS already trims output, we do this because
             * a field might have intentional whitespace at the end that
             * gets removed by DB_PORTABILITY_RTRIM under another driver.
             */
            $this->_rtrimArrayValues($arr);
        }
        if ($this->options['portability'] & DB_PORTABILITY_NULL_TO_EMPTY) {
            $this->_convertNullArrayValuesToEmpty($arr);
        }
        return DB_OK;
    }

    public function freeResult($result)
    {
    }

    public function numCols($result)
    {
        if (! $result->valid()) {
            return $this->raiseError();
        }

        return count($result->current());
    }

    public function numRows($result)
    {
        return count($result);
    }

    public function commit()
    {
        try {
            $this->connection->commit();
        } catch (PDOException $ex) {
            return $this->raiseCompatError($ex);
        }
        return DB_OK;
    }

    public function rollback()
    {
        try {
            $this->connection->rollBack();
        } catch (PDOException $ex) {
            return $this->raiseCompatError($ex);
        }
        return DB_OK;
    }

    public function affectedRows()
    {
        if (DB::isManip($this->last_query)) {
            return $this->num_affected_rows;
        }
        return 0;
    }

    public function errorNative()
    {
        return $this->connection->errorCode();
    }

    public function quoteIdentifier($str)
    {
        return $this->connection->escapeIdentifier($str);
    }

    public function quote($str)
    {
        return $this->quoteSmart($str);
    }

    public function escapeSimple($str)
    {
        return substr($this->connection->quote($str), 1, -1);
    }

    public function getSpecialQuery($type)
    {
        switch ($type) {
            case 'tables':
                return 'SHOW TABLES';
            case 'views':
                return DB_ERROR_NOT_CAPABLE;
            case 'databases':
                return 'SHOW DATABASES';
            default:
                return null;
        }
    }
}
