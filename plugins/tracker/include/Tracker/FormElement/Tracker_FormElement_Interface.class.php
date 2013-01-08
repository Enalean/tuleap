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


/**
 * Base interface for all form elements in trackers, from fieldsets to selectboxes
 */
interface Tracker_FormElement_Interface extends Tracker_Dispatchable_Interface, Tracker_FormElement_IHaveAnId, Tracker_FormElement_Usable{
    
    /**
     * get the permissions for thios tracker
     *
     * @return array
     */
    public function getPermissions();
    

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
