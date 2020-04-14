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

require_once('Collection.class.php');

/**
 * An object that maps key to value.
 * A map cannot contain duplicate keys; each key can map to at most one value.
 */
class Map
{

    public $elements;

    public function __construct()
    {
        $this->elements = array();
    }

    /**
     * @return mixed the value to which this map maps the specified key.
     */
    public function get($key)
    {
        $value = false;
        if ($this->containsKey($key) && isset($this->elements[$key])) {
            $value = $this->elements[$key];
        }
        return $value;
    }

    /**
     * Associates the specified value with the specified key in this map
     */
    public function put($key, $value)
    {
        $this->elements[$key] = $value;
    }

    /**
     * @return true if this map contains no key-value mappings.
     */
    public function isEmpty()
    {
        return empty($this->elements);
    }

    /**
     * @return the number of elements in this map
     */
    public function size()
    {
        return count($this->elements);
    }

    /**
     * @return the keys of this map
     */
    public function getKeys()
    {
        return new Collection(array_keys($this->elements));
    }

    /**
     * @return Collection
     */
    public function getValues()
    {
        return new Collection($this->elements);
    }

    /**
     * @return true if this map contains a mapping for the specified key.
     */
    public function containsKey($key)
    {
        return isset($this->elements[$key]);
    }

    /**
     * @return true if this map maps one or more keys to the specified value.
     */
    public function containsValue($value)
    {
        return in_array($value, $this->elements);
    }

    /**
     * Compares the specified object with this map for equality.
     * @param obj the reference object with which to compare.
     * @return bool true if this object is the same as the obj argument; false otherwise.
     */
    public function equals($obj)
    {
        if (is_a($obj, "Map") && $this->size() === $obj->size()) {
            if ($this->getKeys()->equals($obj->getKeys())) {
                $is_identical = true;
                $obj_elements = $obj->elements;
                foreach ($this->elements as $key => $element) {
                    if ($obj_elements[$key] !== $element) {
                        $is_identical = false;
                        break;
                    }
                }
                return $is_identical;
            }
        }
        return false;
    }

    /**
     * remove a mapping
     */
    public function remove($key, $wanted)
    {
        $compare_with_equals = method_exists($wanted, 'equals');
        $removed = false;
        if ($this->containsKey($key) && isset($this->elements[$key])) {
            if (
                ($compare_with_equals && $wanted->equals($this->elements[$key]))
                || (!$compare_with_equals && (
                 (method_exists($this->elements[$key], 'equals') && $this->elements[$key]->equals($wanted))
                 || ($wanted === $this->elements[$key])))
            ) {
                unset($this->elements[$key]);
                $removed = true;
            }
        }
        return $removed;
    }
}
