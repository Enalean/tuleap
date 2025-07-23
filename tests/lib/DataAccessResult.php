<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;

/**
 * @deprecated See \Tuleap\DB\DataAccessObject
 */
class DataAccessResult implements LegacyDataAccessResultInterface // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    /**
     * $da stores data access object
     * @deprecated
     */
    protected $da;

    /**
     * $result stores a resultset
     */
    protected $result;

    protected $_current; //phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore
    protected $_row; //phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore
    private $instance_callback = null;

    /**
     * @deprecated
     */
    public function __construct($da, $result)
    {
        $this->da     = $da;
        $this->result = $result;
        if (! is_bool($result)) {
            $this->_current = -1;
            $this->_row     = false;
            $this->rewind();
        }
    }

    /**
     * @deprecated
     */
    #[\Override]
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Allow to create an object instead of an array when iterating over results
     *
     * @param callable $instance_callback The callback to use to create object
     *
     * @deprecated
     *
     * @return LegacyDataAccessResultInterface
     */
    #[\Override]
    public function instanciateWith($instance_callback)
    {
        $this->instance_callback = $instance_callback;
        return $this;
    }

    /**
     * Returns an array from query row or false if no more rows
     *
     * @deprecated
     *
     * @return mixed
     */
    #[\Override]
    public function getRow()
    {
        $row = $this->current();
        $this->next();
        return $row;
    }

    /**
     * Returns the number of rows affected
     *
     * @deprecated
     *
     * @return int
     */
    #[\Override]
    public function rowCount()
    {
        return $this->da->numRows($this->result);
    }

    /**
     * Returns false if no errors or returns a MySQL error message
     *
     * @deprecated
     *
     * @return mixed
     */
    #[\Override]
    public function isError()
    {
        $error = $this->daIsError();
        if (! empty($error)) {
            return $error;
        } else {
            return false;
        }
    }

    protected function daIsError()
    {
        return $this->da->isError();
    }

    // {{{ Iterator
    /**
     * @deprecated
     * @return array Return the current element
     */
    #[\Override]
    public function current(): mixed
    {
        if ($this->instance_callback) {
            return call_user_func_array($this->instance_callback, [$this->_row]);
        } else {
            return $this->_row;
        }
    }

    /**
     * Move forward to next element.
     *
     * @deprecated
     */
    #[\Override]
    public function next(): void
    {
        $this->_current++;
        $this->_row = $this->daFetch();
    }

    /**
     * @deprecated
     */
    protected function daFetch()
    {
        return $this->da->fetch($this->result);
    }

    /**
     * Check if there is a current element after calls to rewind() or next().
     *
     * @deprecated
     */
    #[\Override]
    public function valid(): bool
    {
        return $this->_row !== false;
    }

    /**
     * Rewind the Iterator to the first element.
     *
     * @deprecated
     */
    #[\Override]
    public function rewind(): void
    {
        if ($this->rowCount() > 0) {
            $this->daSeek();
            $this->next();
            $this->_current = 0;
        }
    }

    /**
     * @deprecated
     */
    protected function daSeek()
    {
        $this->da->dataSeek($this->result, 0);
    }

    /**
     * Return the key of the current element.
     *
     * @deprecated
     *
     */
    #[\Override]
    public function key(): mixed
    {
        return $this->_current;
    }
    // }}}

    // {{{ Countable
    /**
     * @deprecated
     */
    #[\Override]
    public function count(): int
    {
        return $this->rowCount();
    }

    //}}}

    /**
     * @deprecated
     */
    #[\Override]
    public function freeMemory()
    {
    }
}
