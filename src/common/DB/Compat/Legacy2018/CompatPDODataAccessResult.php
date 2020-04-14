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

namespace Tuleap\DB\Compat\Legacy2018;

/**
 * @deprecated
 */
final class CompatPDODataAccessResult implements LegacyDataAccessResultInterface
{
    /**
     * @var \PDOStatement|null
     */
    private $pdo_statement;
    /**
     * @var \ArrayIterator
     */
    private $result_iterator;
    private $instance_callback;


    public function __construct(?\PDOStatement $pdo_statement = null)
    {
        $this->pdo_statement = $pdo_statement;
        if ($this->pdo_statement !== null) {
            $this->result_iterator = new \ArrayIterator($this->pdo_statement->fetchAll(\PDO::FETCH_BOTH));
        }
    }

    /**
     * @deprecated
     */
    public function getResult()
    {
        return $this;
    }

    /**
     * Allow to create an object instead of an array when iterating over results
     *
     * @param callback $instance_callback The callback to use to create object
     *
     * @deprecated
     *
     * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface
     */
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
    public function rowCount()
    {
        if ($this->pdo_statement === null) {
            return false;
        }
        try {
            return $this->pdo_statement->rowCount();
        } catch (\PDOException $ex) {
            return false;
        }
    }

    /**
     * @deprecated
     */
    public function columnCount()
    {
        if ($this->pdo_statement === null) {
            return false;
        }
        try {
            return $this->pdo_statement->columnCount();
        } catch (\PDOException $ex) {
            return false;
        }
    }

    /**
     * Returns false if no errors or returns a MySQL error message
     *
     * @deprecated
     *
     * @return mixed
     */
    public function isError()
    {
        if ($this->pdo_statement === null) {
            return 'Error encountered while retrieving data';
        }
        $error_info = $this->pdo_statement->errorInfo();

        if ($error_info[0] === '00000') {
            return false;
        }

        return $error_info[2];
    }

    /**
     * @deprecated
     * @return false|array Return the current element
     * @psalm-ignore-falsable-return
     */
    public function current()
    {
        if ($this->result_iterator === null) {
            return false;
        }

        $row = false;
        if ($this->result_iterator->current() !== null) {
            $row = $this->result_iterator->current();
        }

        if ($this->instance_callback) {
            return call_user_func_array($this->instance_callback, array($row));
        }

        return $row;
    }

    /**
     * Move forward to next element.
     *
     * @deprecated
     *
     * @return void
     */
    public function next()
    {
        $this->result_iterator->next();
    }

    /**
     * Check if there is a current element after calls to rewind() or next().
     *
     * @deprecated
     *
     * @return bool
     */
    public function valid()
    {
        if ($this->result_iterator === null) {
            return false;
        }
        return $this->result_iterator->valid();
    }

    /**
     * @deprecated
     */
    public function seek($position)
    {
        try {
            $this->result_iterator->seek($position);
        } catch (\OutOfBoundsException $ex) {
        }
    }

    /**
     * Rewind the Iterator to the first element.
     *
     * @deprecated
     *
     * @return void
     */
    public function rewind()
    {
        $this->result_iterator->rewind();
    }

    /**
     * Return the key of the current element.
     *
     * @deprecated
     *
     * @return mixed
     */
    public function key()
    {
        return $this->result_iterator->key();
    }

    /**
     * @deprecated
     *
     * @return int the number the global function count() should show
     */
    public function count()
    {
        return $this->rowCount();
    }

    /**
     * @deprecated
     */
    public function freeMemory()
    {
        $this->pdo_statement->closeCursor();
    }
}
