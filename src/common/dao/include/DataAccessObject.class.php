<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface;
use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;

/**
 * @deprecated See \Tuleap\DB\DataAccessObject
 */
class DataAccessObject
{
    /**
     * Private
     * $da stores data access object
     * @var LegacyDataAccessInterface
     * @deprecated
     */
    public $da;

    /**
     * @var bool
     */
    private $throw_exception_on_errors = false;

    /**
     * @var string
     * @deprecated
     */
    protected $table_name;

    /**
     * Constructs the Dao
     * @param $da LegacyDataAccessInterface
     * @deprecated
     */
    public function __construct(?LegacyDataAccessInterface $da = null)
    {
        $this->table_name = 'CLASSNAME_MUST_BE_DEFINE_FOR_EACH_CLASS';
        $this->da = $da ? $da : CodendiDataAccess::instance();
    }

    /**
     * @deprecated
     */
    public function startTransaction()
    {
        $this->da->startTransaction();
    }

    /**
     * After having called this method, all DB errors will be converted to exception
     * @deprecated
     */
    public function enableExceptionsOnError()
    {
        $this->throw_exception_on_errors = true;
    }

    /**
     * @deprecated
     */
    public function commit()
    {
        $this->da->commit();
        if ($this->da->isError() && $this->throw_exception_on_errors) {
            throw new DataAccessException($this->da->isError());
        }
    }

    /**
     * @deprecated
     */
    public function rollBack()
    {
        $this->da->rollback();
    }

    /**
     * @deprecated
     * @return LegacyDataAccessInterface
     */
    public function getDa()
    {
        return $this->da;
    }

    /**
     * For SELECT queries
     *
     * @param string $sql the query string
     * @throws DataAccessQueryException
     *
     * @deprecated
     * @return DataAccessResult|false
     */
    public function retrieve($sql, $params = array())
    {
        $result = $this->da->query($sql, $params);
        if (! $this->handleError($result, $sql)) {
            return false;
        }
        return $result;
    }

    /**
     * Like retrieve, but returns only the first row.
     *
     * @param string $sql the query string
     *
     * @deprecated
     *
     * @return array|false
     */
    protected function retrieveFirstRow($sql)
    {
        return $this->retrieve($sql)->getRow();
    }

    /**
     * Like retrieve, but returns only the number of matching rows
     *
     * @param string $sql
     *
     * @deprecated
     *
     * @return int
     */
    protected function retrieveCount($sql)
    {
        return $this->retrieve($sql)->count();
    }

    /**
     * Like retrieve, but returns only the ids.
     *
     * @param string $sql the query string
     *
     * @deprecated
     *
     * @return array of string
     */
    protected function retrieveIds($sql)
    {
        return $this->extractIds($this->retrieve($sql));
    }

    /**
     * Extracts ids from a DataAccessResult.
     *
     * @param DataAccessResult $dar
     *
     * @deprecated
     *
     * @return array of string
     */
    private function extractIds(LegacyDataAccessResultInterface $dar)
    {
        $ids = array();
        foreach ($dar as $row) {
            $ids[] = (int) $row['id'];
        }
        return $ids;
    }

    //! An accessor
    /**
     * For INSERT, UPDATE and DELETE queries
     *
     * @param string $sql the query string
     * @throws DataAccessQueryException
     *
     * @deprecated
     *
     * @return bool true if success
     */
    public function update($sql, $params = array())
    {
        $result = $this->da->query($sql, $params);
        return $this->handleError($result, $sql);
    }

    /**
     * @deprecated
     */
    private function handleError(LegacyDataAccessResultInterface $dar, $sql)
    {
        if ($dar->isError()) {
            if ($this->throw_exception_on_errors) {
                throw new DataAccessQueryException($this->getErrorMessage($dar, $sql));
            } else {
                trigger_error($this->getErrorMessage($dar, $sql));
                return false;
            }
        } else {
            return true;
        }
    }

    /**
     * @deprecated
     */
    private function getErrorMessage(LegacyDataAccessResultInterface $dar, $sql)
    {
        $trace = debug_backtrace();
        $i     = isset($trace[1]) ? 1 : 0;
        return $dar->isError() . ' ==> ' . $sql . " @@ " . $trace[$i]['file'] . ' at line ' . $trace[$i]['line'];
    }

    /**
     * execute and get the last insert id
     *
     * @param string $sql statement (INSERT INTO ...)
     *
     * @deprecated
     *
     * @return int|false the last insert id or false if there is an error
     *
     * @psalm-ignore-falsable-return
     */
    protected function updateAndGetLastId($sql)
    {
        if ($inserted = $this->update($sql)) {
            $inserted = $this->da->lastInsertId();
        }
        return $inserted;
    }

    /**
     * Prepare ranking of items.
     *
     * @param   int $id  The id of the item to rank. 0 if the item doesn't exist.
     * @param   int $parent_id   The id of the element used to group items
     * @param   mixed $rank    The rank asked for the items. Possible values are :
     *                       '--'        => do not change the rank
     *                       'beginning' => to put item before each others
     *                       'end'       => to put item after each others
     *                       'up'        => to put item before previous sibling
     *                       'down'      => to put item after next sibling
     *                       <int>       => to put item at a specific position.
     *                   Please note that for a new item ($id = 0) you must not use
     *                   '--', 'up' or 'down' value
     * @param   string $primary_key the column name of the primary key. Default 'id'
     * @param   string $parent_key the column key used to groups items. Default 'parent_id'
     * @param   string $rank_key the column key used to rank items. Default 'rank'
     *
     * @deprecated
     *
     * @return  mixed false if there is no rank to update of the numerical
     *          value of the new rank of the item. If return 'null' it means
     *          that sth wrong happended.
     */
    protected function prepareRanking(
        string $table_name,
        $id,
        $parent_id,
        $rank,
        $primary_key = 'id',
        $parent_key = 'parent_id',
        $rank_key = 'rank',
        ?string $parent_group_key = null,
        ?int $parent_group_id = null
    ) {
        $newRank = null;

        $additional_where_statement = '';
        if ($parent_group_key !== null && $parent_group_id !== null) {
            $additional_where_statement = sprintf(
                ' AND %s = %d',
                $this->da->quoteSmartSchema($parent_group_key),
                $parent_group_id
            );
        }

        // First, check if there is already some items
        $sql = sprintf(
            'SELECT NULL' .
                       ' FROM ' . $this->da->quoteSmartSchema($table_name) .
                       ' WHERE ' . $parent_key . ' = %d ' .
                       ' %s',
            $parent_id,
            $additional_where_statement
        );
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError() && $dar->rowCount() == 0) {
            // No items: nice, just set the first one to 0.
            $newRank = 0;
        } else {
            switch ((string) $rank) {
                case '--':
                    $sql = sprintf(
                        'SELECT ' . $rank_key .
                               ' FROM ' . $this->da->quoteSmartSchema($table_name) .
                               ' WHERE ' . $primary_key . ' = %d',
                        (int) $id
                    );
                    $dar = $this->retrieve($sql);
                    if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
                        $row = $dar->current();
                        $newRank = $row[$rank_key];
                    }
                    break;
                case 'end':
                    // Simple case: just pickup the most high rank in the table
                    // and add 1 to be laster than the first.
                    $sql = sprintf(
                        'SELECT MAX(' . $rank_key . ')+1 as ' . $rank_key .
                               ' FROM ' . $this->da->quoteSmartSchema($table_name) .
                               ' WHERE ' . $parent_key . ' = %d ' .
                               ' %s',
                        $parent_id,
                        $additional_where_statement
                    );
                    $dar = $this->retrieve($sql);
                    if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
                        $row = $dar->current();
                        $newRank = $row[$rank_key];
                    }
                    break;

                case 'up':
                case 'down':
                    // Those 2 cases are quite complex and are only mandatory if
                    // you want to 'Move up' or 'Move down' an item. If you can
                    // only select in a select box you can remove this part of
                    // the code.

                    // The general idea here is: we want to move up (or down) an
                    // item but we only know it's id and the sens (up/down) of the
                    // slide. Our goal is to exchange the rank value of the item
                    // behind (in case of up) with the current one.

                    // This is done in 2 steps:
                    // * first fetch the item_id and the rank of the item we want
                    //   to stole the place.
                    // * then exchange the 2 rank values.

                    if ($rank == 'down') {
                        $op    = '>';
                        $order = 'ASC';
                    } else {
                        $op    = '<';
                        $order = 'DESC';
                    }

                    // This SQL query aims to get the item_id and the rank of the item
                    // Just behind us (for 'up' case).
                    // In your implementation, USING(parent_id) should refer to the field
                    // that group all the items in one list.
                    $sql = sprintf(
                        'SELECT i1.' . $primary_key . ' as id, i1.' . $rank_key . ' as ' . $rank_key .
                                   ' FROM ' . $this->da->quoteSmartSchema($table_name) . ' i1' .
                                   '  INNER JOIN ' . $this->da->quoteSmartSchema($table_name) . ' i2 USING(' . $parent_key . ')' .
                                   ' WHERE i2.' . $primary_key . ' = %d' .
                                   '   AND i1.' . $parent_key . ' = %d' .
                                   '   AND i1.' . $rank_key . ' %s i2.' . $rank_key .
                                   ' ORDER BY i1.' . $rank_key . ' %s' .
                                   ' LIMIT 1',
                        $id,
                        $parent_id,
                        $op,
                        $order
                    );
                    $dar = $this->retrieve($sql);
                    if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
                        $row = $dar->current();
                        // This query exchange the two values.
                        // Warning: the order is very important, please check that
                        // your final query work as expected.
                        $sql = sprintf(
                            'UPDATE ' . $this->da->quoteSmartSchema($table_name) . ' i1, ' . $this->da->quoteSmartSchema($table_name) . ' i2' .
                                   ' SET i1.' . $rank_key . ' = i2.' . $rank_key . ', i2.' . $rank_key . ' = %d' .
                                   ' WHERE i1.' . $primary_key . ' = %d ' .
                                   '  AND i2.' . $primary_key . ' = %d',
                            $row[$rank_key],
                            $row['id'],
                            $id
                        );
                        $this->update($sql);
                        $newRank = false;
                    }
                    break;

                case 'beginning':
                    // This first part is quite simple: just pickup the lower rank
                    // in the table
                    $sql = sprintf(
                        'SELECT MIN(' . $rank_key . ') as ' . $rank_key .
                               ' FROM ' . $this->da->quoteSmartSchema($table_name) .
                               ' WHERE ' . $parent_key . ' = %d ' .
                               ' %s',
                        $parent_id,
                        $additional_where_statement
                    );
                    $dar = $this->retrieve($sql);
                    if ($dar && !$dar->isError()) {
                        $row = $dar->current();
                        $rank = $row[$rank_key];
                    }
                    // Very important: no break here, because we have to update all
                    // ranks upper:
                    // no break;

                default:
                    // Here $rank is a numerical value that represent the rank after
                    // one item (user selected 'After XXX' in select box).
                    // The idea is to move up all the ranks upper to this value and to
                    // return the current value as the new rank.
                    $sql = sprintf(
                        'UPDATE ' . $this->da->quoteSmartSchema($table_name) .
                               ' SET ' . $rank_key . ' = ' . $rank_key . ' + 1' .
                               ' WHERE ' . $parent_key . ' = %d' .
                               '  AND ' . $rank_key . ' >= %d ' .
                               ' %s',
                        $parent_id,
                        $rank,
                        $additional_where_statement
                    );
                    $updated = $this->update($sql);
                    if ($updated) {
                        $newRank = $rank;
                    }
            }
        }
        return $newRank;
    }

    /**
     * Return the result of 'FOUND_ROWS()' SQL method for the last query.
     * @deprecated
     */
    public function foundRows()
    {
        $sql = "SELECT FOUND_ROWS() as nb";
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError()) {
            $row = $dar->getRow();
            return $row['nb'];
        } else {
            return false;
        }
    }

    /**
     * Optimize the query execution by using GROUP_CONCAT
     *
     * see http://dev.mysql.com/doc/refman/5.1/en/group-by-functions.html#function_group-concat
     * Warning group_concat is truncated by group_concat_max_len system variable
     * Please adjust the settings in /etc/my.cnf to be sure to retrieve all matching artifacts.
     * The default is 1024 (1K) wich is not enough. For example 50000 matching artifacts take ~ 500K
     *
     * @deprecated
     */
    public function setGroupConcatLimit()
    {
        $this->retrieve("SET SESSION group_concat_max_len = 134217728");
    }

    /**
     * @deprecated
     *
     * @return string
     */
    protected function searchExactMatch($string)
    {
        return $this->da->quoteLikeValueSurround($string);
    }

    /**
     * Given a sentence, split at every space and prepare for a search on $field with LIKE
     *
     * @param String $field
     * @param String $string
     *
     * @deprecated
     *
     * @return String
     */
    protected function searchExplodeMatch($field, $string)
    {
        return implode(
            " OR $field LIKE ",
            array_map(
                array($this, 'searchExactMatch'),
                explode(' ', $string)
            )
        );
    }
}
