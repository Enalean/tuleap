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

/**
 * Here we need a model
 */

class TestsPluginSuitePresenter {
    protected $name;
    protected $title;
    protected $id;
    protected $value;
    protected $selected;
    protected $classes  = array();
    protected $children = array();
    protected $prefix;
    
    public function __construct($prefix, $name, $value, $selected = false) {
        $this->name     = $name;
        $this->value    = $value;
        $this->selected = $selected;
        $this->prefix   = $prefix;
    }
    public function prefix() {
        return $this->prefix;
    }
    public function setId($id) {
        $this->id = $id;
    }
    
    public function setTitle($title) {
        $this->title = $title;
    }
    
    public function addClass($class) {
        if (!in_array($class, $this->classes)) {
            $this->classes[] = $class;
        }
    }
    
    public function addChild( TestsPluginSuitePresenter $child) {
        $childName = $child->name();
        if (isset($this->children[$childName])) {
            $parent = $this->children[$childName];
            $parent->addChildren($child->children());
        } else {
            $this->children[$childName] = $child;
        }
    }
    
    public function addChildren( array $children) {
        foreach ($children as $child) {
            $this->addChild($child);
        }
    }
    
    public function getChild($name, $default = null) {
        if ($this->hasChild($name)) {
            $default =& $this->children[$name];
        }
        return $default;
    }
    
    public function hasChild($name) {
        return isset($this->children[$name]);
    }
        
    public function isSelected() {
        return $this->selected;
    }
    
    
    public function checked() {
        if ($this->isSelected()) {
            return ' checked="checked"';
        }
        return '';
    }
    
    public function name() {
        return $this->name;
    }
    
    public function id() {
        if (isset($this->id) && $this->id != '') {
            return sprintf(' id="%s"', $this->id);
        }
    }
    
    public function classes() {
        $classes = implode(' ', $this->classes);
        if (count($this->children) > 0) {
            $classes.=' category';
        }
        return sprintf(' class="%s"', trim($classes));
    }
    
    public function title() {
        $template = '%s';
        if (count($this->children) > 0) {
            $template = '<strong>%s</strong>';
        }
        if (isset($this->title)) {
            return sprintf($template, $this->title);
        }
        return sprintf($template, $this->name);
    }
    
    public function value() {
        return $this->value;
    }
    
    public function children() {
        $return = array_values($this->children);
        sort($return);
        return $return;
    }
    
    public function hasChildren() {
        return count($this->children) > 0;
    }
}

?>