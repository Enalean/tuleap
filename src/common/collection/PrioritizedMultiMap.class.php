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

require_once('MultiMap.class.php');
require_once('PrioritizedList.class.php');

/**
 * An object that maps key to values. 
 * A multi-map can contain duplicate keys; each key can map to more than one value.
 */
class PrioritizedMultiMap extends MultiMap{
    
    function PrioritizedMultiMap() {
        $this->MultiMap();
        $this->collection_class_name = "PrioritizedList";
    }
    
    /**
     * Associates the specified value with the specified key in this map
     */
    function put($key, $value, $priority = 0) {
        $col = $this->_getCollection($key);
        $col->add($value, $priority);
    }
}
?>