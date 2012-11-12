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

class Workflow_Transition_ConditionsCollection implements Iterator {

    /** @var array of Workflow_Transition_Condition */
    private $conditions = array();

    /** @var int */
    private $current;

    // {{{ Iterator
    /**
     * @return array Return the current element
     */
    public function current() {
        return $this->conditions[$this->current];
    }

    /**
     * Move forward to next element.
     *
     * @return void
     */
    public function next() {
        $this->current++;
    }

    /**
     * Check if there is a current element after calls to rewind() or next().
     *
     * @return boolean
     */
    public function valid() {
        return isset($this->conditions[$this->current]);
    }

    /**
     * Rewind the Iterator to the first element.
     *
     * @return void
     */
    public function rewind() {
        $this->current = 0;
    }

    /**
     * Return the key of the current element.
     *
     * @return mixed
     */
    public function key() {
        return $this->current;
    }
    // }}}

    public function add(Workflow_Transition_Condition $condition = null) {
        if ($condition) { //pattern null object?
            $this->conditions[] = $condition;
        }
    }

    public function saveObject() {
        foreach ($this->conditions as $condition) {
            $condition->saveObject();
        }
    }
}
?>
