<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class Collection
{

    public $elements;

    public function __construct($initial_array = '')
    {
        if (is_array($initial_array)) {
            $this->elements = $initial_array;
        } else {
            $this->elements = array();
        }
    }

    /**
     * add the element to the collection
     */
    public function add($element)
    {
        $this->elements[] = $element;
    }

    /**
     * @return true if this collection contains the specified element
     */
    public function contains($wanted)
    {
        $compare_with_equals = method_exists($wanted, 'equals');
        $found = false;
        if (!$compare_with_equals) {
            return in_array($wanted, $this->elements);
        } else {
            $it = $this->iterator();
            while (!$found && $it->valid()) {
                $element = $it->current();
                if ($wanted->equals($element)) {
                    $found = true;
                }
            }
        }
        return $found;
    }

    /**
     * @return Iterator to iterate through the elements
     */
    public function iterator()
    {
        $it = new ArrayIterator($this->elements);
        return $it;
    }

    /**
     * Compares the specified object with this collection for equality.
     * @param obj the reference object with which to compare.
     * @return bool true if this object is the same as the obj argument; false otherwise.
     */
    public function equals($obj)
    {
        if (is_a($obj, "Collection") && $this->size() === $obj->size()) {
            //We walk through the first collection to see if the second
            //contains each value. Remember that there is no order, and
            //we cannot see $obj->elements (protected)
            $it = $this->iterator();
            $is_identical = true;
            while ($it->valid() && $is_identical) {
                $val = $it->current();
                if (!($obj->contains($val))) {
                    $is_identical = false;
                }
                $it->next();
            }
            if ($is_identical) {
                //We walk through the second collection to see if the first
                //contains each value. Remember that there is no order, and
                //we cannot see $obj->elements (protected)
                $it = $obj->iterator();
                $is_identical = true;
                while ($it->valid() && $is_identical) {
                    $val = $it->current();
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
    public function size()
    {
        return count($this->elements);
    }

    /**
     * @return true if the collection is empty
     */
    public function isEmpty()
    {
         return $this->size() === 0;
    }

    /**
     * Removes a single instance of the specified element from this collection,
     * if it is present
     * @param element element to be removed from this collection, if present.
     * @return bool true if this collection changed as a result of the call
     */
    public function remove($wanted)
    {
        $compare_with_equals = method_exists($wanted, 'equals');
        //function in_array doesn't work with object ?!
        foreach ($this->elements as $key => $value) {
            if (($compare_with_equals && $wanted->equals($value))
                || (!$compare_with_equals && ((method_exists($value, 'equals') && $value->equals($wanted)) || ($wanted === $value)))) {
                unset($this->elements[$key]);
                return true;
            }
        }
        return false;
    }


    public function toArray()
    {
        return $this->elements;
    }
}
