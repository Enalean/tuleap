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

class TestsPluginSuitePresenter {
    protected $name;
    protected $title;
    protected $id;
    protected $value;
    protected $selected;
    protected $classes  = array();
    protected $children = array();
    
    public function __construct($name, $value, $selected = false) {
        $this->selected = $selected;
        
    }
    
    public function appendChildren( TestsPluginSuitePresenter $children) {
        $this->children[] = $children;
    }
    
    public function prependChildren( TestsPluginSuitePresenter $children) {
        $this->children= array_unshift($this->children, $children);
    }
        
    public function isSelected() {
        if ($this->selected) {
            return ' checked="checked"'; 
        }
        return '';
    }
    
    public function name() {
        return $this->name;
    }
    
    public function setId($id) {
        $this->id = $id;
    }
    
    public function id() {
        if (isset($this->id)) {
            return sprintf(' id="%s"', $this->id);
        }
    }
    
    public function classes() {
        $classes = implode(' ', $this->classes);
        if (count($this->children) > 0) {
            $classes.=' category';
        }
    }
    
    public function title() {
        return $this->name;
    }
    
    public function value() {
        return $this->value;
    }
    
    public function children() {
        return $this->children;
    }
    
    
}

?>