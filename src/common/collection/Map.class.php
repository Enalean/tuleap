<?php
require_once('Collection.class.php');
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id$
 *
 * An object that maps key to value. 
 * A map cannot contain duplicate keys; each key can map to at most one value.
 */
class Map {
    
    var $elements;
    var $keys;
    
    function Map() {
        $this->elements = array();
        $this->keys     =& new Collection();
    }
    
    
    /**
     * @return the value to which this map maps the specified key.
     */
    function &get(&$key) {
        $value = false;
        if ($this->containsKey($key) && isset($this->elements[$key->hashCode()])) {
            $value =& $this->elements[$key->hashCode()];
        }
        return $value;
    }
    
    /**
     * Associates the specified value with the specified key in this map
     */
    function put(&$key, &$value) {
        if (method_exists($key, 'equals') && method_exists($key, 'hashCode')) {
            if (!isset($this->elements[$key->hashCode()])) {
                $this->keys->add($key);
            }
            $this->elements[$key->hashCode()] =& $value;
        } else {
            trigger_error("key parameter must implements equals() and hashCode() methods");
        }
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
    function &getKeys() {
        return $this->keys;
    }
    
    /**
     * @return a collection view of the values contained in this map.
     */
    function &getValues() {
        $col =& new Collection($this->elements);
        return $col;
    }
    
    /**
     * @return true if this map contains a mapping for the specified key.
     */
    function containsKey(&$key) {
        return $this->keys->contains($key);
    }
    
    /**
     * @return true if this map maps one or more keys to the specified value.
     */
    function containsValue(&$value) {
        $col =& $this->getValues();
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
                $my_keys    =& $this->getKeys();
                $obj_keys   =& $obj->getKeys();
                $obj_values =& $obj->getValues();
                $it =& $my_keys->iterator();
                while($it->valid() && $is_identical) {
                    $val =& $it->current();
                    if (!($obj_values->contains($this->get($val)))) {
                        $is_identical = false;
                    }
                    $it->next();
                }
                $it =& $obj_keys->iterator();
                while($it->valid() && $is_identical) {
                    $val =& $it->current();
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
    function remove(&$key, &$wanted) {
        $compare_with_equals = method_exists($wanted, 'equals');
        $removed = false;
        if ($this->containsKey($key) && isset($this->elements[$key->hashCode()])) {
            if (!$compare_with_equals && !(version_compare(phpversion(), '5', '>=') && is_object($wanted))) {
                $temp = $wanted;
                $wanted = uniqid('test');
            }
            if (($compare_with_equals && $wanted->equals($this->elements[$key->hashCode()])) 
             || (!$compare_with_equals && ((method_exists($this->elements[$key->hashCode()], 'equals') && $this->elements[$key->hashCode()]->equals($wanted)) || ($wanted === $this->elements[$key->hashCode()])))) {
                unset($this->elements[$key->hashCode()]);
                $this->keys->remove($key);
                $removed = true;
            }
            if (!$compare_with_equals && !(version_compare(phpversion(), '5', '>=') && is_object($wanted))) {
                $wanted = $temp;
            }
        }
        return $removed;
    }
    
    /**
     * remove a key
     */
    function removeKey(&$key) {
        $removed = false;
        if ($this->containsKey($key) && isset($this->elements[$key->hashCode()])) {
            unset($this->elements[$key->hashCode()]);
            $this->keys->remove($key);
            $removed = true;
        }
        return $removed;
    }
}
?>