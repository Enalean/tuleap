<?php
/**
 *
 * Copyright (C) Villanova University 2010.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

/**
 * SearchObjectFactory Class
 *
 * This is a factory class to build objects for managing searches.
 *
 * @author      Demian Katz <demian.katz@villanova.edu>
 * @access      public
 */
class SearchObjectFactory {
    /**
     * initSearchObject
     *
     * This constructs a search object for the specified engine.
     *
     * @access  public
     * @param   string  $engine     The type of SearchObject to build (Solr/Summon).
     * @return  mixed               The search object on success, false otherwise
     */
    static function initSearchObject($engine = 'Solr')
    {
        global $configArray;
        $path = "{$configArray['Site']['local']}/sys/SearchObject/{$engine}.php";
        if (is_readable($path)) {
            require_once $path;
            $class = 'SearchObject_' . $engine;
            if (class_exists($class)) {
                $recommend = new $class();
                return $recommend;
            }
        }
        
        return false;
    }
    
    /**
     * deminify
     *
     * Construct an appropriate Search Object from a MinSO object.
     *
     * @access  public
     * @param   object  $minSO      The MinSO object to use as the base.
     * @return  mixed               The search object on success, false otherwise
     */
    static function deminify($minSO)
    {
        // To avoid excessive constructor calls, we'll keep a static cache of
        // objects to use for the deminification process:
        static $objectCache = array();
        
        // Figure out the engine type for the object we're about to construct:
        switch($minSO->ty) {
        case 'Summon':
        case 'SummonAdvanced':
            $type = 'Summon';
            break;
        case 'WorldCat':
        case 'WorldCatAdvanced':
            $type = 'WorldCat';
            break;
        default:
            $type = 'Solr';
            break;
        }
        
        // Construct a new object if we don't already have one:
        if (!isset($objectCache[$type])) {
            $objectCache[$type] = self::initSearchObject($type);
        }
        
        // Populate and return the deminified object:
        $objectCache[$type]->deminify($minSO);
        return $objectCache[$type];
    }
}
?>