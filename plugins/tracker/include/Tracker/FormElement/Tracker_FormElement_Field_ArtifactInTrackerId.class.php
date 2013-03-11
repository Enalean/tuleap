<?php
/**
 * Copyright (c) Tuleap, 2013. All Rights Reserved.
 *
 * Originally written by Yoann Celton, 2013. Jtekt Europe.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class Tracker_FormElement_Field_ArtifactInTrackerId extends Tracker_FormElement_Field_ArtifactId {
          
    public function getCriteriaWhere($criteria) {
        if ($criteria_value = $this->getCriteriaValue($criteria)) {
            return $this->buildMatchExpression("c.artifact_in_tracker_id", $criteria_value);
        }
        return '';
    }
    
    public function getQuerySelect() {
        return "a.in_tracker_id AS `". $this->name ."`";
    }
    
    /**
     * Get the "group by" statement to retrieve field values
     */
    public function getQueryGroupby() {
        return "a.in_tracker_id";
    }
    
    public function fetchChangesetValue($artifact_id, $changeset_id, $value, $from_aid = null) {
        if ($from_aid != null) {
            return '<a class="direct-link-to-artifact" href="'.TRACKER_BASE_URL.'/?'. http_build_query(array('aid' => (int)$artifact_id )).'&from_aid='.$from_aid.'">'. $value .'</a>';
        }
        return '<a class="direct-link-to-artifact" href="'.TRACKER_BASE_URL.'/?'. http_build_query(array('aid' => (int)$artifact_id )).'">'. $value .'</a>';
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
        return '<a href="'.TRACKER_BASE_URL.'/?'. http_build_query(array('aid' => (int)$artifact->id )).'">##'. (int)$artifact->in_tracker_id .'</a>';
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
                $output .= '<a href= "'.$proto.'://'. $GLOBALS['sys_default_domain'].TRACKER_BASE_URL.'/?'. http_build_query(array('aid' => (int)$artifact->id )).'">##'. $value .'</a>';
                break;
            default:
                $output .= '##'.$artifact->in_tracker_id;
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
        $html .= '<a href="#'.TRACKER_BASE_URL.'/?aid=123" onclick="return false;">##3</a>';
        return $html;
    }
    
    /**
     * @return the label of the field (mainly used in admin part)
     */
    public static function getFactoryLabel() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'artifactInTrackerId_label');
    }
    
    /**
     * @return the description of the field (mainly used in admin part)
     */
    public static function getFactoryDescription() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'artifactInTrackerId_description');
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
        $html .= $artifact->in_tracker_id;
        return $html;
    }
}
?>