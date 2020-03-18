<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Workflow_Transition_ConditionsCollection implements ArrayAccess
{

    /** @var array of Workflow_Transition_Condition */
    private $conditions = array();

    // {{{ ArrayAccess
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->conditions[] = $value;
        } else {
            $this->conditions[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->conditions[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->conditions[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->conditions[$offset]) ? $this->conditions[$offset] : null;
    }
    // }}}

    /**
     * Add a condition to the collection
     */
    public function add(?Workflow_Transition_Condition $condition = null)
    {
        if ($condition) { //pattern null object?
            $this->conditions[] = $condition;
        }
    }

    /**
     * Get the conditions
     *
     * @return array
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
     * Creates new conditions in the database
     */
    public function saveObject()
    {
        foreach ($this->conditions as $condition) {
            $condition->saveObject();
        }
    }

    /**
     * Export conditions to XML
     *
     * @param SimpleXMLElement &$root     the node to which the conditions is attached (passed by reference)
     * @param array            $xmlMapping correspondance between real ids and xml IDs
     *
     * @return void
     */
    public function exportToXml(SimpleXMLElement $root, $xmlMapping)
    {
        if ($this->conditions) {
            $child = $root->addChild('conditions');
            foreach ($this->conditions as $condition) {
                $condition->exportToXML($child, $xmlMapping);
            }
        }
    }

    /**
     * Validates all conditions in the collections
     *
     * @return bool true if all conditions are satisfied
     */
    public function validate($fields_data, Tracker_Artifact $artifact, $comment_body)
    {
        foreach ($this->getConditions() as $condition) {
            if (! $condition->validate($fields_data, $artifact, $comment_body)) {
                return false;
            }
        }
        return true;
    }
}
