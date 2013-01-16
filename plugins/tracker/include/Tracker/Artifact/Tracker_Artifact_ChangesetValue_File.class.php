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

/**
 * Manage values in changeset for files fields
 */
class Tracker_Artifact_ChangesetValue_File extends Tracker_Artifact_ChangesetValue implements Countable, ArrayAccess, Iterator {
    
    /**
     * @var array of Tracker_FileInfo
     */
    protected $files;
    
    /**
     * Constructor
     *
     * @param Tracker_FormElement_Field_File $field       The field of the value
     * @param boolean                        $has_changed If the changeset value has chnged from the previous one
     * @param array                          $files       array of Tracker_FileInfo
     */
    public function __construct($id, $field, $has_changed, $files) {
        parent::__construct($id, $field, $has_changed);
        $this->files = $files;
    }
    
    /**
     * spl\Countable
     *
     * @return int the number of files
     */
    public function count() {
        return count($this->files);
    }
    
    /**
     * spl\ArrayAccess
     *
     * @param int $offset to retrieve
     *
     * @return mixed value at given offset
     */
    public function offsetGet($offset) {
        return $this->files[$offset];
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
        $this->files[$offset] = $value;
    }
    
    /**
     * spl\ArrayAccess
     *
     * @param int $offset to check
     *
     * @return boolean wether the offset exists
     */
    public function offsetExists($offset) {
        return isset($this->files[$offset]);
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
        return $this->files[$this->index];
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
        return isset($this->files[$this->index]);
    }
    
    /**
     * Get the files infos
     *
     * @return array of Tracker_FileInfo the files
     */
    public function getFiles() {
        return $this->files;
    }
    
    /**
     * Return a string that will be use in SOAP API
     * as the value of this ChangesetValue_File 
     *
     * @return Array The value of this artifact changeset value for Soap API
     */
    public function getSoapValue() {
        $soap_array = array();
        foreach ($this->getFiles() as $file_info) {
            $soap_array[] = $file_info->getSoapValue();
        }
        return array('file_info' => $soap_array);
    }
    
    /**
     * Returns the value of this changeset value
     *
     * @return mixed The value of this artifact changeset value
     */
    public function getValue() {
        // TODO : implement
        return false;
    }
    
    /**
     * Returns a diff between this changeset value and the one passed in param
     *
     * @param Tracker_Artifact_ChangesetValue_File $changeset_value the changeset value to compare
     *
     * @return string The difference between another $changeset_value, false if no differneces
     */
    public function diff($changeset_value, $format = 'html') {
        if ($this->files !== $changeset_value->getFiles()) {
            $result = '';
            $removed = array();
            foreach (array_diff($changeset_value->getFiles(), $this->files) as $fi) {
                $removed[] = $fi->getFilename();
            }
            if ($removed = implode(', ', $removed)) {
                $result .= $removed .' '.$GLOBALS['Language']->getText('plugin_tracker_artifact','removed');
            }
            $added = array();
            foreach (array_diff($this->files, $changeset_value->getFiles()) as $fi) {
                $added[] = $fi->getFilename();
            }
            if ($added = implode(', ', $added)) {
                if ($result) {
                    $result .= PHP_EOL;
                }
                $result .= $added .' '.$GLOBALS['Language']->getText('plugin_tracker_artifact','added');
            }
            return $result;
        }
        return false;
    }
    
     /**
     * Returns the "set to" for field added later
     *
     * @return string The sentence to add in changeset
     */
    public function nodiff() {
        if (!empty($this->files)) {
            $result = '';
            $added = array();
            foreach($this->files as $file) {
                $added[] = $file->getFilename();
            }
            if ($added = implode(', ', $added)) {
                if ($result) {
                    $result .= PHP_EOL;
                }
                $changes .= $added .' '.$GLOBALS['Language']->getText('plugin_tracker_artifact','added');
            }
            return $result;
        }
    }

}
?>
