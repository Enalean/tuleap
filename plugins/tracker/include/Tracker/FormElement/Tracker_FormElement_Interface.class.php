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

require_once(dirname(__FILE__).'/../Tracker_Dispatchable_Interface.class.php');

/**
 * Base interface for all form elements in trackers, from fieldsets to selectboxes
 */
interface Tracker_FormElement_Interface extends Tracker_Dispatchable_Interface {

    /**
     * @return the label of the formElement (mainly used in admin part)
     */
    public static function getFactoryLabel();
    
    /**
     * @return the description of the formElement (mainly used in admin part)
     */
    public static function getFactoryDescription();
    
    /**
     * @return the path to the icon to use an element
     */
    public static function getFactoryIconUseIt();
    
    /**
     * @return the path to the icon to create an element
     */
    public static function getFactoryIconCreate();
    
    public static function getFactoryUniqueField(/*Tracker $tracker*/);
    /**
     *  Get the id
     *
     * @return int
     */
    public function getId();

    /**
     * get the permissions for thios tracker
     *
     * @return array
     */
    public function getPermissions();
    
    /**
     * say if a formElement is used
     *
     * @return bool
     */
    public function isUsed();


    /**
     * Transforms FormElement into a SimpleXMLElement
     * 
     * @param SimpleXMLElement $root        the node to which the FormElement is attached (passed by reference)
     * @param array            &$xmlMapping correspondance between real ids and xml IDs
     * @param int              $index       of the last field in the array
     *
     * @return void
     */
    public function exportToXML($root, &$xmlMapping, &$index);
}
?>
