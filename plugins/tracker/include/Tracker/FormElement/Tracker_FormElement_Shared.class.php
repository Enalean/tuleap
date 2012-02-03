<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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

require_once 'Tracker_FormElement_Description.class.php';

class Tracker_FormElement_Shared implements Tracker_FormElement_Description {

    /**
     * @return the label of the formElement (mainly used in admin part)
     */
    public static function getFactoryLabel() {
        return 'Shared field';
    }
    
    /**
     * @return the description of the formElement (mainly used in admin part)
     */
    public static function getFactoryDescription() {
        return 'Use a field defined in another tracker';
    }
     
    /**
     * @return the path to the icon to use an element
     */
    public static function getFactoryIconUseIt() {
        return $GLOBALS['HTML']->getImagePath('ic/bug-poo.png');
    }
    
    /**
     * @return the path to the icon to create an element
     */
    public static function getFactoryIconCreate() {
        return $GLOBALS['HTML']->getImagePath('ic/bug-poo.png');
    }
    
    /**
     * @return bool say if the field is a unique one
     */
    public static function getFactoryUniqueField() {
        return false;
    }
    
    /**
     * Display the form to create a new formElement
     * 
     * @param TrackerManager  $tracker_manager The service
     * @param Codendi_Request $request         The data coming from the user
     * @param User            $current_user    The user who mades the request
     * @param string          $type            The internal name of type of the field
     * @param string          $factory_label   The label of the field (At factory 
     *                                         level 'Selectbox, File, ...')
     *
     * @return void
     */
    public function displayAdminCreate(TrackerManager $tracker_manager, $request, $current_user, $type, $factory_label) {
        $hp = Codendi_HTMLPurifier::instance();
        $url   = TRACKER_BASE_URL.'/?tracker='. (int)$this->tracker_id .'&amp;func=admin-formElements&amp;create-formElement['.  $hp->purify($type, CODENDI_PURIFIER_CONVERT_HTML) .']=1';
        echo '<form action="'. $url .'" method="POST">';
        
        echo '<p>Field id:';
        echo '<input type="text" name="formElement_data[field_id]" value="" />';
        echo '</p>';
        
        echo '<input type="submit" name="docreate-formElement" value="Submit" />';
        
        echo '</form>';
    }
    
    
    public function __construct($id, $tracker_id, $parent_id, $name, $label, $description, $use_it, $scope, $required, $notifications, $rank) {
        $this->id            = $id;
        $this->tracker_id    = $tracker_id;
        $this->parent_id     = $parent_id;
        $this->name          = $name;
        $this->label         = $label;
        $this->description   = $description;
        $this->use_it        = $use_it;
        $this->scope         = $scope;
        $this->required      = $required;
        $this->notifications = $notifications;
        $this->rank          = $rank;
    }
}
?>
