<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'DataAccess.class.php';
require_once 'DataAccessResult.class.php';
require_once 'DataAccessResultEmpty.class.php';

/**
 *  Base class for data access objects
 */
class DataAccessObject {
    /**
     * Private
     * $da stores data access object
     * @var DataAccess
     */
    var $da;

    //! A constructor
    /**
    * Constructs the Dao
    * @param $da instance of the DataAccess class
    */
    public function __construct($da = null) {
        $this->table_name = 'CLASSNAME_MUST_BE_DEFINE_FOR_EACH_CLASS';
        $this->da = $da ? $da : CodendiDataAccess::instance();
    }
    public function DataAccessObject($da = null) {
        $this->__construct($da);
    }

    public function startTransaction() {
        $this->da->startTransaction();
    }

    public function commit() {
        $this->da->commit();
    }
    
    public function rollBack() {
        $this->da->rollback();
    }
    
    /**
     * 
     * @return DataAccess
     */
    public function getDa() {
        return $this->da;
    }

    /**
     * For SELECT queries
     *
     * @param $sql the query string
     *
     * @return mixed either false if error or object DataAccessResult
     */
    public function retrieve($sql, $params = array()) {
        $result = $this->da->query($sql, $params);
        if ($error = $result->isError()) {
            $trace = debug_backtrace();
            $i = isset($trace[1]) ? 1 : 0;
            trigger_error(mysql_error() .' '. $error .' ==> '. $sql ." @@ ". $trace[$i]['file'] .' at line '. $trace[$i]['line']);
            $result = false;
        }
        return $result;
    }
    
    /**
     * Like retrieve, but returns only the first row.
     * 
     * @param string $sql the query string
     * 
     * @return mixed
     */
    protected function retrieveFirstRow($sql) {
        return $this->retrieve($sql)->getRow();
    }
    
    /**
     * Like retrieve, but returns only the ids.
     * 
     * @param string $sql the query string
     * @return array of string
     */
    protected function retrieveIds($sql) {
        return $this->extractIds($this->retrieve($sql));
    }
    
    /**
     * Extracts ids from a DataAccessResult.
     * 
     * @param DataAccessResult $dar
     * @return array of string
     */
    private function extractIds(DataAccessResult $dar) {
        $ids = array();
        foreach ($dar as $row) { 
            $ids[] = $row['id'];
        }
        return $ids;
    }

    //! An accessor
    /**
     * For INSERT, UPDATE and DELETE queries
     *
     * @param $sql the query string
     *
     * @return boolean true if success
     */
    public function update($sql, $params = array()) {
        $result = $this->da->query($sql, $params);
        if ($error = $result->isError()) {
            $trace = debug_backtrace();
            $i = isset($trace[1]) ? 1 : 0;
            trigger_error($error .' ==> '. $sql ." @@ ". $trace[$i]['file'] .' at line '. $trace[$i]['line']);
            return false;
        } else {
            return true;
        }
    }
    
    /**
     * execute and get the last insert id
     *
     * @param string $sql statement (INSERT INTO ...)
     *
     * @return int the last insert id or false if there is an error 
     */
    protected function updateAndGetLastId($sql) {
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
     * @return  mixed false if there is no rank to update of the numerical
     *          value of the new rank of the item. If return 'null' it means
     *          that sth wrong happended.
     */
    protected function prepareRanking($id, $parent_id, $rank, $primary_key = 'id', $parent_key = 'parent_id', $rank_key = 'rank') {
        $newRank = null;
        
        // First, check if there is already some items
        $sql = sprintf('SELECT NULL'.
                       ' FROM '. $this->table_name .
                       ' WHERE '. $parent_key .' = %d',
                       $parent_id);
        $dar = $this->retrieve($sql);
        if($dar && !$dar->isError() && $dar->rowCount() == 0) {
            // No items: nice, just set the first one to 0.
            $newRank = 0;
        }
        else {
            switch((string)$rank) {
            case '--':
                $sql = sprintf('SELECT '. $rank_key .
                               ' FROM '. $this->table_name .
                               ' WHERE '. $primary_key .' = %d',
                               (int)$id);
                $dar = $this->retrieve($sql);
                if($dar && !$dar->isError() && $dar->rowCount() == 1) {
                    $row = $dar->current();
                    $newRank = $row[$rank_key];
                }
                break;
            case 'end':
                // Simple case: just pickup the most high rank in the table
                // and add 1 to be laster than the first.
                $sql = sprintf('SELECT MAX('. $rank_key .')+1 as '. $rank_key .
                               ' FROM '. $this->table_name .
                               ' WHERE '. $parent_key .' = %d',
                               $parent_id);
                $dar = $this->retrieve($sql);
                if($dar && !$dar->isError() && $dar->rowCount() == 1) {
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
                $sql = sprintf('SELECT i1.'. $primary_key .' as id, i1.'. $rank_key .' as '. $rank_key .
                                   ' FROM '. $this->table_name .' i1'.
                                   '  INNER JOIN '. $this->table_name .' i2 USING('. $parent_key .')'.
                                   ' WHERE i2.'. $primary_key .' = %d'.
                                   '   AND i1.'. $parent_key .' = %d'.
                                   '   AND i1.'. $rank_key .' %s i2.'. $rank_key .
                                   ' ORDER BY i1.'. $rank_key .' %s'.
                                   ' LIMIT 1',
                                   $id,
                                   $parent_id,
                                   $op,
                                   $order);
                $dar = $this->retrieve($sql);
                if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
                    $row = $dar->current();
                    // This query exchange the two values.
                    // Warning: the order is very important, please check that
                    // your final query work as expected.
                    $sql = sprintf('UPDATE '. $this->table_name .' i1, '. $this->table_name .' i2'.
                                   ' SET i1.'. $rank_key .' = i2.'. $rank_key .', i2.'. $rank_key .' = %d'.
                                   ' WHERE i1.'. $primary_key .' = %d '.
                                   '  AND i2.'. $primary_key .' = %d',
                                   $row[$rank_key],
                                   $row['id'],
                                   $id);
                    $this->update($sql);
                    $newRank = false;
                }
                break;

            case 'beginning':
                // This first part is quite simple: just pickup the lower rank
                // in the table
                $sql = sprintf('SELECT MIN('. $rank_key .') as '. $rank_key .
                               ' FROM '. $this->table_name .
                               ' WHERE '. $parent_key .' = %d',
                               $parent_id);
                $dar = $this->retrieve($sql);
                if($dar && !$dar->isError()) {
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
                $sql = sprintf('UPDATE '. $this->table_name .
                               ' SET '. $rank_key .' = '. $rank_key .' + 1'.
                               ' WHERE '. $parent_key .' = %d'.
                               '  AND '. $rank_key .' >= %d',
                               $parent_id, $rank);
                $updated = $this->update($sql);
                if($updated) {
                    $newRank = $rank;
                }
            }
        }
        return $newRank;
    }

    /**
     * Return the result of 'FOUND_ROWS()' SQL method for the last query.
     */
    function foundRows() {
        $sql = "SELECT FOUND_ROWS() as nb";
        $dar = $this->retrieve($sql);
        if($dar && !$dar->isError()) {
            $row = $dar->getRow();
            return $row['nb'];
        } else {
            return false;
        }
    }
}
?>
