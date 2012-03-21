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

abstract class Tracker_FormElement_Field_List_Value {
    protected $id;
    
    public function __construct($id) {
        $this->id = $id;
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function setId($id) {
        $this->id = $id;
        return $this;
    }
    
    /**
     * Format a value to json
     *
     * @return string
     */
    public function fetchJson() {
        return array(
            'value'   => $this->getJsonId(),
            'caption' => $this->getLabel(),
        );
    }
    
    public abstract function getJsonId();
    
    public abstract function __toString();
    
    public abstract function getLabel();
    
    public function fetchFormatted() {
        return $this->getLabel();
    }
    
    public function fetchFormattedForCSV() {
        return $this->getLabel();
    }
    
    public function isHidden() {
        return false;
    }
}

?>
