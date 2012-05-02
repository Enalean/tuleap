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
class Map {
    
    var $elements;
    var $keys;
    
    function Map() {
        $this->elements = array();
        $this->keys     = new Collection();
    }
    
    
    /**
     * @return the value to which this map maps the specified key.
     */
    function get($key) {
        $value = false;
        if ($this->containsKey($key) && isset($this->elements[$key])) {
            $value = $this->elements[$key];
        }
        return $value;
    }
    
    /**
     * Associates the specified value with the specified key in this map
     */
    function put($key, $value) {
        if (!isset($this->elements[$key])) {
            $this->keys->add($key);
        }
        $this->elements[$key] = $value;
    }
    
    /**
     * @return true if this map contains no key-value mappings.
     */
    function isEmpty() {
         return ($this->size() === 0);
    }
    
    /**
     * @return the number of elements in this map
     */
    function size() {
        return count($this->elements);
    }
    
    /**
     * @return the keys of this map
     */
    function getKeys() {
        return $this->keys;
    }
    
    /**
     * @return a collection view of the values contained in this map.
     */
    function getValues() {
        $col = new Collection($this->elements);
        return $col;
    }
    
    /**
     * @return true if this map contains a mapping for the specified key.
     */
    function containsKey($key) {
        return $this->keys->contains($key);
    }
    
    /**
     * @return true if this map maps one or more keys to the specified value.
     */
    function containsValue($value) {
        $col = $this->getValues();
        return $col->contains($value);
    }
    
    /**
     * Compares the specified object with this map for equality.
     * @param obj the reference object with which to compare.
     * @return true if this object is the same as the obj argument; false otherwise.
     */
    function equals($obj) {
        if (is_a($obj, "Map") && $this->size() === $obj->size()) {
            if ($this->keys->equals($obj->getKeys())) {
                $is_identical = true;
                $my_keys    = $this->getKeys();
                $obj_keys   = $obj->getKeys();
                $obj_values = $obj->getValues();
                $it = $my_keys->iterator();
                while($it->valid() && $is_identical) {
                    $val = $it->current();
                    if (!($obj_values->contains($this->get($val)))) {
                        $is_identical = false;
                    }
                    $it->next();
                }
                $it =& $obj_keys->iterator();
                while($it->valid() && $is_identical) {
                    $val = $it->current();
                    if (!($this->containsValue($obj->get($val)))) {
                        $is_identical = false;
                    }
                    $it->next();
                }
                return $is_identical;
            }
        }
        return false;
    }
    
    /**
     * remove a mapping
     */
    function remove($key, $wanted) {
        $compare_with_equals = method_exists($wanted, 'equals');
        $removed = false;
        if ($this->containsKey($key) && isset($this->elements[$key])) {
            if (($compare_with_equals && $wanted->equals($this->elements[$key])) 
             || (!$compare_with_equals && (
                 (method_exists($this->elements[$key], 'equals') && $this->elements[$key]->equals($wanted)) 
                 || ($wanted === $this->elements[$key])))) {
                unset($this->elements[$key]);
                $this->keys->remove($key);
                $removed = true;
            }
        }
        return $removed;
    }
    
    /**
     * remove a key
     */
    function removeKey($key) {
        $removed = false;
        if ($this->containsKey($key) && isset($this->elements[$key])) {
            unset($this->elements[$key]);
            $this->keys->remove($key);
            $removed = true;
        }
        return $removed;
    }
}
?>