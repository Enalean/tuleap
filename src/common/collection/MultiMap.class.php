<?php
require_once('Map.class.php');
require_once('Collection.class.php');
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id:MultiMap.class.php 4446 2006-12-08 16:18:48 +0000 (Fri, 08 Dec 2006) ahardyau $
 *
 * An object that maps key to values. 
 * A multi-map can contain duplicate keys; each key can map to more than one value.
 */
class MultiMap {
    
    /* protected */ var $map;
    /* protected */ var $collection_class_name;
    
    function MultiMap() {
        $this->map =& new Map();
        $this->collection_class_name = "Collection";
    }
    
    
    /**
     * @return the Collection to which this map maps the specified key.
     */
    function &get(&$key) {
        return $this->map->get($key);
    }
    
    /**
     * Associates the specified value with the specified key in this map
     */
    function put(&$key, &$value) {
        if (method_exists($key, 'equals') && method_exists($key, 'hashCode')) {
            $col =& $this->_getCollection($key);
            $col->add($value);
        } else {
            trigger_error("key parameter must implements equals() and hashCode() methods");
        }
    }
    
    /**
     * @return true if this map contains no key-value mappings.
     */
    function isEmpty() {
         return $this->map->isEmpty();
    }
    
    /**
     * @return the collection corresponding to the key
     * @access protected
     */
    function &_getCollection(&$key) {
        $col =& $this->map->get($key);
        if (!$col) {
            $col =& new $this->collection_class_name();
            $this->map->put($key, $col);
        }
        return $col;
    }
    
    /**
     * @return the keys of this map
     */
    function &getKeys() {
        return $this->map->getKeys();
    }
    
}
?>