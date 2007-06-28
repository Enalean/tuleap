<?php
require_once('Collection.class.php');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * LinkedList
 */
class LinkedList extends Collection{
    
    function LinkedList($initial_array = '') {
        $this->Collection($initial_array);
    }
    
    /**
     * add the element add the end of the LinkedList
     */
    function add(&$element) {
        $this->elements[] =& $element;
    }
    
    /**
     * Compares the specified object with this LinkedList for equality.
     * @param obj the reference object with which to compare.
     * @return true if this object is the same as the obj argument; false otherwise.
     */
    function equals(&$obj) {
        if (is_a($obj, "Collection") && $this->size() === $obj->size()) {
            //We walk through the two LinkedList to see if both
            //contain same values
            $it1 =& $this->iterator();
            $it2 =& $obj->iterator();
            $is_identical = true;
            while ($it1->valid() && $is_identical) {
                $val1 =& $it1->current();
                $val2 =& $it2->current();
                if (!(version_compare(phpversion(), '5', '>=') && is_object($val1))) {
                    $temp = $val1;
                    $val1 = uniqid('test');
                }
                if ($val1 !== $val2) {
                    $is_identical = false;
                }
                if (!(version_compare(phpversion(), '5', '>=') && is_object($val1))) {
                    $val1 = $temp;
                }
                $it1->next();
                $it2->next();
            }
            return $is_identical;
        }
        return false;
    }
}
?>