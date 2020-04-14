<?php
/**
* Copyright Enalean (c) 2013 - 2018. All rights reserved.
* Tuleap and Enalean names and logos are registrated trademarks owned by
* Enalean SAS. All other trademarks or names are properties of their respective
* owners.
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

class Tracker_SemanticCollection implements ArrayAccess, Iterator
{
    /**
     * @var Tracker_Semantic[]
     */
    private $semantics_by_name;
    /**
     * @var Tracker_Semantic[]
     */
    private $semantics;

    public function __construct()
    {
        $this->semantics         = [];
        $this->semantics_by_name = [];
    }

    public function add(Tracker_Semantic $semantic)
    {
        $this->semantics[] = $semantic;

        $this->semantics_by_name[$semantic->getShortName()] = $semantic;
    }

    public function insertAfter($semantic_shortname, Tracker_Semantic $semantic)
    {
        $position = 0;
        foreach ($this->semantics as $index => $previous_semantic) {
            if ($previous_semantic->getShortName() === $semantic_shortname) {
                $position = $index + 1;
                break;
            }
        }

        array_splice($this->semantics, $position, 0, array($semantic));
        $this->semantics_by_name[$semantic->getShortName()] = $semantic;
    }

    public function offsetExists($offset)
    {
        return isset($this->semantics_by_name[$offset]);
    }

    public function offsetGet($offset)
    {
        /** @psalm-suppress NullableReturnStatement */
        return isset($this->semantics_by_name[$offset]) ? $this->semantics_by_name[$offset] : null;
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset) || ! isset($this->semantics_by_name[$offset])) {
            $this->add($value);
        } else {
            $this->semantics_by_name[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->semantics_by_name[$offset]);

        foreach ($this->semantics as $index => $semantic) {
            if ($semantic->getShortName() === $offset) {
                unset($this->semantics[$index]);
                break;
            }
        }
    }

    public function current()
    {
        return current($this->semantics);
    }

    public function next()
    {
        next($this->semantics);
    }

    public function key()
    {
        return key($this->semantics);
    }

    public function valid()
    {
        return key($this->semantics) !== null;
    }

    public function rewind()
    {
        reset($this->semantics);
    }
}
