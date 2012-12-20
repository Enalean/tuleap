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

require_once 'IProvideDataAccessResult.class.php';

/**
 *  Fetches MySQL database rows as objects
 */
class DataAccessResult implements IProvideDataAccessResult {
    /**
     * $da stores data access object
     */
    protected $da;
    
    /**
     * $result stores a resultset
     * TODO: remove legacy code database.php and put this attribute in the protected scope !
     */
    public $result;

    protected $_current;
    protected $_row;
    private $instance_callback = null;
    
    public function __construct($da, $result) {
        $this->da     = $da;
        $this->result = $result;
        if (!is_bool($result)) {
            $this->_current = -1;
            $this->_row     = false;
            $this->rewind();
        }
    }

    public function getResult() {
        return $this->result;
    }

    /**
     * Allow to create an object instead of an array when iterating over results
     *
     * @param callback $instance_callback The callback to use to create object
     *
     * @return \DataAccessResult
     */
    public function instanciateWith($instance_callback) {
        $this->instance_callback = $instance_callback;
        return $this;
    }

    /**
     * Returns an array from query row or false if no more rows
     * @return mixed
     */
    public function getRow() {
        $row = $this->current();
        $this->next();
        return $row;
    }

    /**
     * Returns the number of rows affected
     * @return int
     */
    public function rowCount() {
        return $this->da->numRows($this->result);
    }

    /**
     * Returns false if no errors or returns a MySQL error message
     * @return mixed
     */
    public function isError() {
        $error= $this->daIsError();
        if (!empty($error))
            return $error;
        else
            return false;
    }

    protected function daIsError() {
        return $this->da->isError();
    }

    // {{{ Iterator
    /**
     * @return array Return the current element
     */
    public function current() {
        if ($this->instance_callback) {
            return call_user_func_array($this->instance_callback, array($this->_row));
        } else {
            return $this->_row;
        }
    }
    
    /**
     * Move forward to next element. 
     *
     * @return void 
     */
    public function next() {
        $this->_current++;
        $this->_row = $this->daFetch();
    }

    protected function daFetch() {
        return $this->da->fetch($this->result);
    }
    
    /**
     * Check if there is a current element after calls to rewind() or next(). 
     *
     * @return boolean 
     */
    public function valid() {
        return $this->_row !== false;
    }
    
    /**
     * Rewind the Iterator to the first element.
     *
     * @return void
     */
    public function rewind() {
        if ($this->rowCount() > 0) {
            $this->daSeek();
            $this->next();
            $this->_current = 0;
        }
    }

    protected function daSeek() {
        $this->da->dataSeek($this->result, 0);
    }

    /**
     * Return the key of the current element. 
     * 
     * @return mixed 
     */
    public function key() {
        return $this->_current;
    }
    // }}}
    
    // {{{ Countable
    /**
     * @return int the number the global function count() should show
     */
    public function count() {
        return $this->rowCount();
    }

    //}}}
}
?>
