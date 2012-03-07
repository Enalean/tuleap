<?php
/**
 * Copyright (c) STMicroelectronics, 2005. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2005
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
 * TreeNode class handle one Node in a Tree representation.
 *
 * A Tree is a composition of Node. A Node can have children or Not.
 * A Node without Children can be considered as a Leaf.
 * This class do not propose default method to walk through. You can use an
 * iterator to iterate on Childs or a Visitor to walk through the hierarchy.
 *
 * @author: Manuel Vacelet <manuel.vacelet@st.com>
 * @see: Visitor
 */
class TreeNode /*implements Visitable*/ {
    /**
     * @type mixed
     */
    var $data;

    /**
     * @type array
     */
    var $children;
    
    /**
     * @type TreeNode Reference
     */
    var $parentNode;
    
    private $id;
    
    
    /**
     * Constructor
     */
    function TreeNode($data=null) {
        $this->id = uniqid();
        /*if(func_num_args() !== 0) {
            trigger_error(get_class($this).'::TreeNode => Do not accept arguments', E_USER_ERROR);
        }*/
        $this->data       = $data;
        $this->children   = array();
        $this->parentNode = null;
    }
    
    public function getId() {
        return $this->id;
    }

    /**
     * Set data for current node.
     *
     * @param mixed $d Any kind of data stored in a Node
     */
    function setData($d) {
        $this->data = $d;
    }


    /**
     * Return a reference on data of current node.
     * 
     * @return mixed (reference)
     */
    function &getData() {
        return $this->data;
    }
    

    /**
     * Set current node parent.
     * 
     * @access private
     * @return mixed (reference)
     */
    function _setParentNode(&$node) {
        if(is_object($node) && is_a($node, get_class($this))) {
            $this->parentNode =& $node;
        }
        else {
            trigger_error(get_class($this).'::setParentNode => require: "'.get_class($this).'" given: "'.gettype($c).'"', E_USER_ERROR);
        }
    }


    /**
     * Return a reference on current node parent.
     * 
     * @return mixed (reference)
     */
    function &getParentNode() {
        return $this->parentNode;
    }


    /**
     * Add a new TreeNode as next child.
     *
     * @param TreeNode &$c A TreeNode (reference call)
     */
    function addChild(&$c) {
        if(is_object($c) && is_a($c, get_class($this))) {
            if($this->children === null) {
                $this->children = array();
            }
            $c->_setParentNode($this);
            $this->children[] =& $c;
        }
        else {
            trigger_error(get_class($this).'::addChild => require: "'.get_class($this).'" given: "'.gettype($c).'"', E_USER_ERROR);
        }
    }


    /**
     * Remove a child.
     * 
     * @param int $key Id of child to remove.
     */
    function removeChild($key) {
        if(isset($key) && is_int($key) && is_array($this->children) && array_key_exists($key, $this->children)) {
            unset($this->children[$key]);
        }
        else {
            trigger_error(get_class($this).'::removeChild => require: "int" given: "'.gettype($key).'"', E_USER_ERROR);
        }
    }


    /**
     * Return reference on asked child.
     *
     * @param int $key Id of child to return
     * @return TreeNode reference.
     */
    function &getChild($key) {
        if(isset($key) && is_int($key) && is_array($this->children) && array_key_exists($key, $this->children)) {
            return $this->children[$key];
        }
        else {
            trigger_error(get_class($this).'::getChild => require: "int" given: "'.gettype($key).'"', E_USER_ERROR);
        }
    }      


    /**
     * Get children 
     *
     * @return array of TreeNode
     */
    function &getChildren() {
        return $this->children;
    }


    /**
     * Set children. 
     *
     * @param &$children array of TreeNode
     */
    function setChildren(&$children) {
        if(is_array($this->children)) {
            $this->children =& $children;
        }
        else {
            trigger_error(get_class($this).'::setChildren => require: "array" given: "'.gettype($children).'"', E_USER_ERROR);
        }
    }


    /**
     * Return true if Node has children. 
     *
     * @return boolean.
     */
    function hasChildren() {
        return (count($this->children) > 0);
    }


    /**
     * Visitor entry. 
     *
     * @param Visitor
     */
    function accept(&$visitor, $params = null) {
        $visitor->visit($this, $params);
    }
}

?>