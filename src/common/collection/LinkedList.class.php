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
 * LinkedList
 */
class LinkedList extends Collection
{

    public function __construct($initial_array = '')
    {
        parent::__construct($initial_array);
    }

    /**
     * add the element add the end of the LinkedList
     */
    public function add($element)
    {
        $this->elements[] = $element;
    }

    /**
     * Compares the specified object with this LinkedList for equality.
     * @param obj the reference object with which to compare.
     * @return bool true if this object is the same as the obj argument; false otherwise.
     */
    public function equals($obj)
    {
        if (is_a($obj, "Collection") && $this->size() === $obj->size()) {
            //We walk through the two LinkedList to see if both
            //contain same values
            $it1 = $this->iterator();
            $it2 = $obj->iterator();
            $is_identical = true;
            while ($it1->valid() && $is_identical) {
                $val1 = $it1->current();
                $val2 = $it2->current();
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
