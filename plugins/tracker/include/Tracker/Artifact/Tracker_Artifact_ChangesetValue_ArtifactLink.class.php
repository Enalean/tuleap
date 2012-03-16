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
 * Manage values in changeset for 'artifact link' fields
 */
class Tracker_Artifact_ChangesetValue_ArtifactLink extends Tracker_Artifact_ChangesetValue {
    
    /**
     * @var array of artifact_id => Tracker_ArtifactLinkInfo
     */
    protected $artifact_links;
    
    /**
     * Constructor
     *
     * @param Tracker_FormElement_Field_ArtifactLink $field        The field of the value
     * @param boolean                                $has_changed  If the changeset value has chnged from the previous one
     * @param array                                  $artifact_links array of artifact_id => Tracker_ArtifactLinkInfo
     */
    public function __construct($id, $field, $has_changed, $artifact_links) {
        parent::__construct($id, $field, $has_changed);
        $this->artifact_links = $artifact_links;
    }
    
    /**
     * Check if there are changes between current and new value
     *
     * @param array $new_value array of artifact ids
     *
     * @return bool true if there are differences
     */
    public function hasChanges($new_value) {
        if (empty($new_value['new_values']) && empty($new_value['removed_values'])) {
            // no changes
            return false;
        } else {
            $array_new_values = explode(',', $new_value['new_values']);
            $array_cur_values = $this->getArtifactIds();
            sort($array_new_values);
            sort($array_cur_values);
            return $array_new_values !== $array_cur_values;
        }
    }
    
    /**
     * Returns a diff between current changeset value and changeset value in param
     *
     * @param Tracker_Artifact_ChangesetValue $changeset_value The changeset value to compare to this changeset value
     *
     * @return string The difference between another $changeset_value, false if no differences
     */
    public function diff($changeset_value, $format = 'html') {
        $previous = $changeset_value->getValue();
        $next     = $this->getValue();
        $changes = false;
        if ($previous != $next) {
            $removed_elements = array_diff($previous, $next);
            $removed_arr = array();
            $method = 'getLabel';
            if ($format === 'html') {
                $method = 'getUrl';
            }
            foreach ($removed_elements as $art_id => $removed_element) {
                $removed_arr[] = $removed_element->$method();
            }
            $removed = implode(', ', $removed_arr);
            $added_elements = array_diff($next, $previous);
            $added_arr = array();
            foreach ($added_elements as $art_id => $added_element) {
                $added_arr[] = $added_element->$method();
            }
            $added   = implode(', ', $added_arr);
            if (empty($next)) {
                $changes = ' '.$GLOBALS['Language']->getText('plugin_tracker_artifact','cleared');
            } else if (empty($previous)) {
                $changes = ' '.$GLOBALS['Language']->getText('plugin_tracker_artifact','set_to').' '.$added;
            } else if (count($previous) == 1 && count($next) == 1) {
                $changes = ' '.$GLOBALS['Language']->getText('plugin_tracker_artifact','changed_from'). ' '.$removed .' '.$GLOBALS['Language']->getText('plugin_tracker_artifact','to').' '.$added;
            } else {
                if ($removed) {
                    $changes = $removed .' '.$GLOBALS['Language']->getText('plugin_tracker_artifact','removed');
                }
                if ($added) {
                    if ($changes) {
                        $changes .= PHP_EOL;
                    }
                    $changes .= $added .' '.$GLOBALS['Language']->getText('plugin_tracker_artifact','added');
                }
            }
        }
        return $changes;
    }
    
    /**
     * Returns the "set to" for field added later
     *
     * @return string The sentence to add in changeset
     */
    public function nodiff() {
        $next = $this->getValue();
        if (!empty($next)) {
            $result = '';
            $added_arr = array();
            foreach($next as $art_id => $added_element) {
                $added_arr[] = $added_element->getUrl();
            }
            $added   = implode(', ', $added_arr);
            $result = ' '.$GLOBALS['Language']->getText('plugin_tracker_artifact','set_to').' '.$added;
            return $result;
        }
    }
    
    /**
     * Returns the SOAP value of this changeset value
     *
     * @return string The value of this artifact changeset value for Soap API
     */
    public function getSoapValue() {
        return implode(', ', $this->getArtifactIds());
    }
    
    /**
     * Returns the value of this changeset value
     *
     * @return mixed The value of this artifact changeset value
     */
    public function getValue() {
        return $this->artifact_links;
    }
    
    public function getArtifactIds() {
        return array_keys($this->artifact_links);
    }
}
?>
