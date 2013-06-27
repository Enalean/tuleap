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


class Tracker_FormElement_Field_List_Bind_StaticValue extends Tracker_FormElement_Field_List_BindValue {
    
    /**
     *
     * @var string 
     */
    protected $label;
    
    /**
     *
     * @var string 
     */
    protected $description;
    
    /**
     *
     * @var int 
     */
    protected $rank;
    
    public function __construct($id, $label, $description, $rank, $is_hidden) {
        parent::__construct($id, $is_hidden);
        $this->label       = $label;
        $this->description = $description;
        $this->rank        = $rank;
    }
    
    /**
     * 
     * @return string
     */
    public function __toString() {
        return $this->label ? $this->label : '';
    }
    
    /**
     * 
     * @return int
     */
    public function getId() {
        return $this->id;
    }
    
    /**
     * 
     * @param int $id
     * @return \Tracker_FormElement_Field_List_Bind_StaticValue
     */
    public function setId($id) {
        $this->id = (int) $id;
        return $this;
    }
    
    /**
     * 
     * @return string
     */
    public function getLabel() {
        return $this->label;
    }
    
    /**
     * 
     * @param string $label
     * @return \Tracker_FormElement_Field_List_Bind_StaticValue
     */
    public function setLabel($label) {
        $this->label = (string) $label;
        return $this;
    }
    
    /**
     * 
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }
    
    /**
     * 
     * @param string $description
     * @return \Tracker_FormElement_Field_List_Bind_StaticValue
     */
    public function setDescription($description) {
        $this->description = $description;
        return $this;
    }

    /**
     * 
     * @return int
     */
    public function getRank() {
        return $this->rank;
    }
    
    /**
     * 
     * @param int $rank
     * @return \Tracker_FormElement_Field_List_Bind_StaticValue
     */
    public function setRank($rank) {
        $this->rank = (int) $rank;
        return $this;
    }
}
?>
