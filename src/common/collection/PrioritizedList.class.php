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

require_once('LinkedList.class.php');

/**
 * PrioritizedList
 */
class PrioritizedList extends LinkedList
{
    public $priorities;
    public function __construct($initial_array = '')
    {
        parent::__construct($initial_array);
        $this->priorities = array();
        if (count($this->elements)) {
            $this->priorities[] = array_keys(array_fill(0, count($this->elements), 0));
        }
    }

    /**
     * add the element add the end of the PrioritizedList
     */
    public function add($element, $priority = 0)
    {
        $this->elements[] = $element;
        $this->priorities[$priority][] = count($this->elements) - 1;
    }

    public function iterator()
    {
        $tab = array();
        krsort($this->priorities);
        foreach ($this->priorities as $elements) {
            foreach ($elements as $position) {
                $tab[] = $this->elements[$position];
            }
        }
        $it = new ArrayIterator($tab);
        return $it;
    }
}
