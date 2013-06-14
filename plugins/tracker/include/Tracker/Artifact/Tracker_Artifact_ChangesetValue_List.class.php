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

require_once('common/include/Codendi_HTMLPurifier.class.php');

/**
 * Manage values in changeset for string fields
 */
class Tracker_Artifact_ChangesetValue_List extends Tracker_Artifact_ChangesetValue implements Countable, ArrayAccess, Iterator {
    
    /**
     * @var array (of ListValue) the list of list values
     */
    protected $list_values;
    
    /**
     * Constructor
     *
     * @param Tracker_FormElement_Field_String $field       The field of the value
     * @param boolean                          $has_changed If the changeset value has chnged from the previous one
     * @param array                            $list_values The list of values
     */
    public function __construct($id, $field, $has_changed, array $list_values) {
        parent::__construct($id, $field, $has_changed);
        $this->list_values = $list_values;
    }
    
    /**
     * spl\Countable
     *
     * @return int the number of files
     */
    public function count() {
        return count($this->list_values);
    }
    
    /**
     * spl\ArrayAccess
     *
     * @param int $offset to retrieve
     *
     * @return mixed value at given offset
     */
    public function offsetGet($offset) {
        return $this->list_values[$offset];
    }
    
    /**
     * spl\ArrayAccess
     *
     * @param int   $offset to modify
     * @param mixed $value  new value
     *
     * @return void
     */
    public function offsetSet($offset, $value) {
        $this->list_values[$offset] = $value;
    }
    
    /**
     * spl\ArrayAccess
     *
     * @param int $offset to check
     *
     * @return boolean wether the offset exists
     */
    public function offsetExists($offset) {
        return isset($this->list_values[$offset]);
    }
    
    /**
     * spl\ArrayAccess
     *
     * @param int $offset to delete
     *
     * @return void
     */
    public function offsetUnset($offset) {
        unset($this->files[$offset]);
    }
    
    /**
     * spl\Iterator
     *
     * The internal pointer to traverse the collection
     * @var integer
     */
    protected $index;
    
    /**
     * spl\Iterator
     * 
     * @return Tracker_FileInfo the current one
     */
    public function current() {
        return $this->list_values[$this->index];
    }
    
    /**
     * spl\Iterator
     * 
     * @return int the current index
     */
    public function key() {
        return $this->index;
    }
    
    /**
     * spl\Iterator
     * 
     * Jump to the next Tracker_FileInfo
     *
     * @return void
     */
    public function next() {
        $this->index++;
    }
    
    /**
     * spl\Iterator
     *
     * Reset the pointer to the start of the collection
     * 
     * @return Tracker_FileInfo the current one
     */
    public function rewind() {
        $this->index = 0;
    }
    
    /**
     * spl\Iterator
     * 
     * @return boolean true if the current pointer is valid
     */
    public function valid() {
        return isset($this->list_values[$this->index]);
    }
    
    /**
     * Get the list values
     *
     * @return Tracker_FormElement_Field_List_BindValue[]
     */
    public function getListValues() {
        return $this->list_values;
    }
    
    /**
     * Get the value (an array of int)
     *
     * @return array of int The values of this artifact changeset value
     */
    public function getValue() {
        $values = $this->getListValues();
        $array = array();
        foreach ($values as $value) {
            $array[] = $value->getId();
        }
        return $array;
    }
    
    /**
     * Return a string that will be use in SOAP API
     * as the value of this ChangesetValue_List 
     *
     * @return string The value of this artifact changeset value for Soap API
     */
    public function getSoapValue() {
        return array('bind_value' => array_map(array($this, 'getSoapBindValue'), $this->getListValues()));
    }

    private function getSoapBindValue($value) {
        return array(
            'bind_value_id'    => $value->getId(),
            'bind_value_label' => $value->getSoapValue()
        );
    }

    /**
     * Get the diff between this changeset value and the one passed in param
     *
     * @param Tracker_Artifact_ChangesetValue_List $changeset_value the changeset value to compare
     *
     * @return string The difference between another $changeset_value, false if no differneces
     */
    public function diff($changeset_value, $format = 'html') {
        $previous = $changeset_value->getListValues();
        $next     = $this->getListValues();
        $changes = false;
        if ($previous != $next) {
            $removed_elements = array_diff($previous, $next);
            $removed_arr = array();
            foreach ($removed_elements as $removed_element) {
                $removed_arr[] = $removed_element->getLabel();
            }
            $removed = implode(', ', $removed_arr);
            $added_elements = array_diff($next, $previous);
            $added_arr = array();
            foreach ($added_elements as $added_element) {
                $added_arr[] = $added_element->getLabel();
            }
            $added   = implode(', ', $added_arr);
            if (empty($next)) {
                $changes = ' '.$GLOBALS['Language']->getText('plugin_tracker_artifact','cleared');
            } else if (empty($previous)) {
                $changes = ' '.$GLOBALS['Language']->getText('plugin_tracker_artifact','set_to').' '.$added;
            } else if (count($previous) == 1 && count($next) == 1) {
                $changes = ' '.$GLOBALS['Language']->getText('plugin_tracker_artifact','changed_from'). ' '.$removed .' '.$GLOBALS['Language']->getText('plugin_tracker_artifact','to').' '.$added;
            } else {
                if ($removed) {
                    $changes = $removed .' '.$GLOBALS['Language']->getText('plugin_tracker_artifact','removed');
                }
                if ($added) {
                    if ($changes) {
                        $changes .= PHP_EOL;
                    }
                    $changes .= $added .' '.$GLOBALS['Language']->getText('plugin_tracker_artifact','added');
                }
            }
        }
        return $changes;
    }
    
    public function nodiff() {
        $next = $this->getListValues();
        $added_arr = array();
        foreach ($next as $element) {
                $added_arr[] = $element->getLabel();
        }
        $added = implode(', ', $added_arr);
        return ' '.$GLOBALS['Language']->getText('plugin_tracker_artifact','set_to').' '.$added;
    }
}
?>
