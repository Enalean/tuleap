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

require_once('Map.class.php');
require_once('Collection.class.php');

/**
 * An object that maps key to values. 
 * A multi-map can contain duplicate keys; each key can map to more than one value.
 */
class MultiMap {
    
    /* protected */ var $map;
    /* protected */ var $collection_class_name;
    
    function MultiMap() {
        $this->map = new Map();
        $this->collection_class_name = "Collection";
    }
    
    
    /**
     * @return the Collection to which this map maps the specified key.
     */
    function get($key) {
        return $this->map->get($key);
    }
    
    /**
     * Associates the specified value with the specified key in this map
     */
    function put($key, $value) {
        $col = $this->_getCollection($key);
        $col->add($value);
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
    function _getCollection($key) {
        $col = $this->map->get($key);
        if (!$col) {
            $col = new $this->collection_class_name();
            $this->map->put($key, $col);
        }
        return $col;
    }
    
    /**
     * @return the keys of this map
     */
    function getKeys() {
        return $this->map->getKeys();
    }
    
}
?>