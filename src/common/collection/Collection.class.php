<?php
require_once('ArrayIterator.class.php');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id$
 *
 * Collection
 */
class Collection {
    
    /* protected */ var $elements;
    
    function Collection($initial_array = '') {
        if (is_array($initial_array)) {
            $this->elements = $initial_array;
        } else {
            $this->elements = array();
        }
    }
    
    /**
     * add the element to the collection
     */
    function add(&$element) {
        $this->elements[] =& $element;
    }
    
    /**
     * @return true if this collection contains the specified element
     */
    function contains(&$wanted) {
        $compare_with_equals = method_exists($wanted, 'equals');
        //function in_array doesn't work with object ?!
        $found = false;
        if (!$compare_with_equals && !(version_compare(phpversion(), '5', '>=') && is_object($wanted))) {
            $temp = $wanted;
            $wanted = uniqid('test');
        }
        $it =& $this->iterator();
        while(!$found && $it->valid()) {
            $element =& $it->current();
            if (($compare_with_equals && $wanted->equals($element)) 
             || (!$compare_with_equals && ((method_exists($element, 'equals') && $element->equals($temp)) || ($element === $wanted)))) {
                $found = true;
            }
            $it->next();
        }
        if (!$compare_with_equals && !(version_compare(phpversion(), '5', '>=') && is_object($wanted))) {
            $wanted = $temp;
        }
        return $found;
    }
    
    /**
     * @return Iterator to iterate through the elements
     */
    function &iterator() {
        $it =& new ArrayIterator($this->elements);
        return $it;
    }
    
    /**
     * Compares the specified object with this collection for equality.
     * @param obj the reference object with which to compare.
     * @return true if this object is the same as the obj argument; false otherwise.
     */
    function equals(&$obj) {
        if (is_a($obj, "Collection") && $this->size() === $obj->size()) {
            //We walk through the first collection to see if the second
            //contains each value. Remember that there is no order, and
            //we cannot see $obj->elements (protected)
            $it =& $this->iterator();
            $is_identical = true;
            while ($it->valid() && $is_identical) {
                $val =& $it->current();
                if (!($obj->contains($val))) {
                    $is_identical = false;
                }
                $it->next();
            }
            if ($is_identical) {
                //We walk through the second collection to see if the first
                //contains each value. Remember that there is no order, and
                //we cannot see $obj->elements (protected)
                $it =& $obj->iterator();
                $is_identical = true;
                while ($it->valid() && $is_identical) {
                    $val =& $it->current();
                    if (!($this->contains($val))) {
                        $is_identical = false;
                    }
                    $it->next();
                }
            }
            return $is_identical;
        }
        return false;
    }
    
    /**
     * @return the number of elements in this collection
     */
    function size() {
        return count($this->elements);
    }
    
    /**
     * @return true if the collection is empty
     */
    function isEmpty() {
         return $this->size() === 0;
    }
    
    /**
     * Removes a single instance of the specified element from this collection, 
     * if it is present
     * @param element element to be removed from this collection, if present.
     * @return true if this collection changed as a result of the call
     */
    function remove(&$wanted) {
        $compare_with_equals = method_exists($wanted, 'equals');
        //function in_array doesn't work with object ?!
        $found = false;
        if (!$compare_with_equals && !(version_compare(phpversion(), '5', '>=') && is_object($wanted))) {
            $temp = $wanted;
            $wanted = uniqid('test');
        }
        reset($this->elements);
        while((list($key, $value) = each($this->elements)) && !$found) {
            if (($compare_with_equals && $wanted->equals($value)) 
             || (!$compare_with_equals && ((method_exists($value, 'equals') && $value->equals($temp)) || ($wanted === $value)))) {
                unset($this->elements[$key]);
                $found = true;
            }
        }
        if (!$compare_with_equals && !(version_compare(phpversion(), '5', '>=') && is_object($wanted))) {
            $wanted = $temp;
        }
        return $found;
    }
    
    
    function toArray() {
        return $this->elements;
    }
}
?>