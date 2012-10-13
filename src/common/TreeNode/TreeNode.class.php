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
     * @var mixed
     */
    var $object;

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
    function TreeNode($data=null, $id=null) {
        $this->id = ($id === null) ? uniqid() : $id;
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
    
    public function setId($id) {
        $this->id = $id;
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
        if(is_object($node) && is_a($node, 'TreeNode') ) {
            $this->parentNode =& $node;
        }
        else {
            trigger_error(get_class($this).'::setParentNode => require: TreeNode given: "'.  get_class($node).'"', E_USER_ERROR);
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
    function addChild($c) {
        if(is_object($c) && is_a($c, 'TreeNode')) {
            if($this->children === null) {
                $this->children = array();
            }
            $c->_setParentNode($this);
            $this->children[] = $c;
        }
        else {
            trigger_error(get_class($this).'::addChild => require: TreeNode given: "'.get_class($c).'"', E_USER_ERROR);
        }
    }

    /**
     * Allows to define a tree inline (usefull for tests)
     *
     * @return TreeNode
     */
    function addChildren() {
        $child_list = func_get_args();
        foreach ($child_list as $child) {
            $this->addChild($child);
        }
        return $this;
    }

    /**
     * Remove a child.
     * 
     * @param int $key Id of child to remove.
     */
    function removeChild($key, $object = null) {
        if (!$key && $object && is_array($this->children)) {
            $key = array_search($object, $this->children);
        }
        if(isset($key) && is_int($key) && is_array($this->children) && array_key_exists($key, $this->children)) {
            unset($this->children[$key]);
            $this->children = array_values($this->children);
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
     * @param $children array of TreeNode
     */
    function setChildren($children) {
        if(is_array($this->children)) {
            $this->clearChildren();
            foreach ($children as $child) {
                $this->addChild($child);
            }
        }
        else {
            trigger_error(get_class($this).'::setChildren => require: "array" given: "'.gettype($children).'"', E_USER_ERROR);
        }
    }

    /**
     * Remove existing children
     */
    public function clearChildren() {
        $this->children = array();
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
     * @return bool
     */
    private function hasChild(TreeNode $child) {
        return in_array($child, $this->children);
    }

    /**
     * Add the child only if the current node doesn't already contain it
     */
    public function addSingularChild(TreeNode $child) {
        if (!$this->hasChild($child)) {
            $this->addChild($child);
        }
    }

    /**
     * Visitor entry. 
     *
     * @param Visitor
     */
    function accept(&$visitor, $params = null) {
        return $visitor->visit($this, $params);
    }
    
    public function __toString() {
        $children_as_string = '';
        foreach ($this->getChildren() as $child) {
            $children_as_string .= $child->__toString() .",\n";
        }
        return 'TreeNode #'. $this->id ." {\n $children_as_string }\n";
    }

    /**
     * @return array A flat list of all descendant nodes (usefull for tests).
     */
    public function flattenChildren() {
        $flatten_children = array();
        
        foreach($this->getChildren() as $child) {
            $flatten_children = array_merge($flatten_children, $child->flatten());
        }
        
        return $flatten_children;
    }
    
    /**
     * @return array A flat list of this node and all its descendants (usefull for tests).
     */
    public function flatten() {
        return array_merge(array($this), $this->flattenChildren());
    }
    
    public function getObject() {
        return $this->object;
    }
    
    public function setObject($object) {
        $this->object = $object;
    }
}

?>