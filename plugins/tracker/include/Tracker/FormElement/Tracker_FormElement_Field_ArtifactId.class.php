<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2010. All rights reserved
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

class Tracker_FormElement_Field_ArtifactId extends Tracker_FormElement_Field_Integer implements Tracker_FormElement_Field_ReadOnly {
    
    public $default_properties = array();
    
    public function getCriteriaFrom($criteria) {
        return '';
    }
    
    public function getCriteriaWhere($criteria) {
        if ($criteria_value = $this->getCriteriaValue($criteria)) {
            return $this->buildMatchExpression("c.artifact_id", $criteria_value);
        }
        return '';
    }
    
    public function getQuerySelect() {
        return "a.id AS `". $this->name ."`";
    }
    
    public function getQueryFrom() {
        return '';
    }
    
    /**
     * Get the "group by" statement to retrieve field values
     */
    public function getQueryGroupby() {
        return "a.id";
    }
    
    public function fetchChangesetValue($artifact_id, $changeset_id, $value, $from_aid = null) {
        if ($from_aid != null) {
            return '<a class="direct-link-to-artifact" href="'.TRACKER_BASE_URL.'/?'. http_build_query(array('aid' => (int)$value )).'&from_aid='.$from_aid.'">'. $value .'</a>';
        }
        return '<a class="direct-link-to-artifact" href="'.TRACKER_BASE_URL.'/?'. http_build_query(array('aid' => (int)$value )).'">'. $value .'</a>';
    }
    
    /**
     * @return array the available aggreagate functions for this field. empty array if none or irrelevant.
     */
    public function getAggregateFunctions() {
        return array();
    }
    
    /**
     * Display the field as a Changeset value.
     * Used in CSV data export.
     *
     * @param int $artifact_id the corresponding artifact id
     * @param int $changeset_id the corresponding changeset
     * @param mixed $value the value of the field
     *
     * @return string
     */
    public function fetchCSVChangesetValue($artifact_id, $changeset_id, $value) {
        return $value;
    }
    
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
        return $this->fetchArtifactValueReadOnly($artifact, $value);
    }

    /**
     * Fetch the html code to display the field value in artifact in read only mode
     *
     * @param Tracker_Artifact                $artifact The artifact
     * @param Tracker_Artifact_ChangesetValue $value    The actual value of the field
     *
     * @return string
     */
    public function fetchArtifactValueReadOnly(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null) {
        return '<a href="'.TRACKER_BASE_URL.'/?'. http_build_query(array('aid' => (int)$artifact->id )).'">#'. (int)$artifact->id .'</a>';
    }

    /**
     * Fetch artifact value for email
     * @param Tracker_Artifact $artifact
     * @param Tracker_Artifact_ChangesetValue $value
     * @param string $format
     * @return string
     */
    public function fetchMailArtifactValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null, $format='text') {
        $output = '';
        switch ($format) {
            case 'html':
                $proto = ($GLOBALS['sys_force_ssl']) ? 'https' : 'http';
                $output .= '<a href= "'.$proto.'://'. $GLOBALS['sys_default_domain'].TRACKER_BASE_URL.'/?'. http_build_query(array('aid' => (int)$artifact->id )).'">#'. (int)$artifact->id .'</a>';
                break;
            default:
                $output .= '#'.$artifact->id;
                break;
        }
        return $output;
    }

    /**
     * Display the html field in the admin ui
     * @return string html
     */
    protected function fetchAdminFormElement() {
        $html = '';
        $html .= '<a href="#'.TRACKER_BASE_URL.'/?aid=123" onclick="return false;">#42</a>';
        return $html;
    }
    
    /**
     * @return the label of the field (mainly used in admin part)
     */
    public static function getFactoryLabel() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'artifactid_label');
    }
    
    /**
     * @return the description of the field (mainly used in admin part)
     */
    public static function getFactoryDescription() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'artifactid_description');
    }
    
    /**
     * @return the path to the icon
     */
    public static function getFactoryIconUseIt() {
        return $GLOBALS['HTML']->getImagePath('ic/tracker-aid.png');
    }
    
    /**
     * @return the path to the icon
     */
    public static function getFactoryIconCreate() {
        return $GLOBALS['HTML']->getImagePath('ic/tracker-aid--plus.png');
    }
    
    /**
     * Fetch the html code to display the field value in tooltip
     * 
     * @param Tracker_Artifact $artifact
     * @param Tracker_Artifact_ChangesetValue_Integer $value The changeset value of this field
     * @return string The html code to display the field value in tooltip
     */
    protected function fetchTooltipValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null) {
        $html = '';
        $html .= $artifact->getId();
        return $html;
    }

    /**
     * Verifies the consistency of the imported Tracker
     * 
     * @return true if Tracler is ok 
     */
    public function testImport() {
        return true;
    }

    
    /**
     * Validate a value
     *
     * @param Tracker_Artifact $artifact The artifact 
     * @param mixed            $value    data coming from the request. 
     *
     * @return bool true if the value is considered ok
     */
    protected function validate(Tracker_Artifact $artifact, $value) {
        //No need to validate artifact id (read only for all)
        return true;
    }
    
    /**
     * Fetch the element for the submit new artifact form
     *
     * @return string html
     */
     public function fetchSubmit() {
         return '';
     }
     
     /**
     * Fetch the element for the submit new artifact form
     *
     * @return string html
     */
     public function fetchSubmitMasschange($submitted_values=array()) {
         return '';
     }
}
?>