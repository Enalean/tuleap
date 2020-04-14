<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

/**
 * Wrapper of array of columns
 */
class Cardwall_OnTop_Config_ColumnCollection implements ArrayAccess, IteratorAggregate, Countable
{

    /**
     * @var array
     */
    private $columns;

    public function __construct(array $columns = array())
    {
        $this->columns = $columns;
    }

    /**
     * @see ArrayAccess
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->columns[] = $value;
        } else {
            $this->columns[$offset] = $value;
        }
    }

    /**
     * @see ArrayAccess
     */
    public function offsetExists($offset)
    {
        return isset($this->columns[$offset]);
    }

    /**
     * @see ArrayAccess
     */
    public function offsetUnset($offset)
    {
        unset($this->columns[$offset]);
    }

    /**
     * @see ArrayAccess
     */
    public function offsetGet($offset)
    {
        /** @psalm-suppress NullableReturnStatement */
        return isset($this->columns[$offset]) ? $this->columns[$offset] : null;
    }

    /**
     * @see IteratorAggregate
     */
    public function getIterator()
    {
        return new ArrayIterator($this->columns);
    }

    /**
     * @see Countable
     */
    public function count()
    {
        return count($this->columns);
    }

    public function getColumnById($id)
    {
        foreach ($this->columns as $column) {
            if ($column->id == $id) {
                return $column;
            }
        }
    }

    public function getColumnByLabel($label)
    {
        foreach ($this->columns as $column) {
            if ($column->label == $label) {
                return $column;
            }
        }
    }

    public function getRestValue()
    {
        $column_representations = array();
        foreach ($this->columns as $column) {
            $column_representation = new AgileDashboard_ColumnRepresentation();
            $column_representation->build($column);
            $column_representations[] = $column_representation;
        }
        return $column_representations;
    }
}
