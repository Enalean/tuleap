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

require_once('Tracker_FormElement_Field_Text.class.php');
require_once('dao/Tracker_FormElement_Field_StringDao.class.php');

class Tracker_FormElement_Field_WebLink extends Tracker_FormElement_Field_String {


    /**
     * Fetch the html code to display the field value in artifact
     *
     * @param Tracker_Artifact                $artifact         The artifact
     * @param Tracker_Artifact_ChangesetValue $value            The actual value of the field
     * @param array                           $submitted_values The value already submitted by the user
     *
     * @return string
     */
    protected function fetchArtifactValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null, $submitted_values = array()) {
        $html = '';
        $html .= parent::fetchArtifactValue($artifact,  $value , $submitted_values);
        $html .= '<br /><a href="'.$value->getText().'">'.$value->getText().'</a>';
    
        return $html;
    }
    
    /**
     * Return the label of the field (mainly used in admin part)
     *
     * @return string
     */
    public static function getFactoryLabel() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'weblink');
    }
    
    /**
     * Return the description of the field (mainly used in admin part)
     *
     * @return string
     */
    public static function getFactoryDescription() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'weblink_desc');
    }
    
    /**
     * Return the path to the icon
     *
     * @return string
     */
    public static function getFactoryIconUseIt() {
        return $GLOBALS['HTML']->getImagePath('ic/ui-weblink-field.png');
    }
    
    /**
     * Return the path to the icon
     *
     * @return string
     */
    public static function getFactoryIconCreate() {
        return $GLOBALS['HTML']->getImagePath('ic/ui-weblink-field--plus.png');
    }
    
}
?>
