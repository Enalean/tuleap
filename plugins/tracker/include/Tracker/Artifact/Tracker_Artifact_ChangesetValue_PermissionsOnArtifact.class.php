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
require_once('common/dao/UGroupDao.class.php');

/**
 * Manage values in changeset for date fields
 */
class Tracker_Artifact_ChangesetValue_PermissionsOnArtifact extends Tracker_Artifact_ChangesetValue {
    
    /**
     * @var array
     */
    protected $perms;
    protected $used;
    
    /**
     * Constructor
     *
     * @param Tracker_FormElement_Field_Date $field       The field of the value
     * @param boolean                        $has_changed If the changeset value has chnged from the previous one
     * @param array                          $perms   The permissions
     */
    public function __construct($id, $field, $has_changed, $used, $perms) {
        parent::__construct($id, $field, $has_changed);
        $this->perms = $perms;
        $this->used = $used;
    }
    
    /**
     * Get the permissions
     *
     * @return Array the permissions
     */
    public function getPerms() {
        return $this->perms;
    }
    
    /**
     * Return the value of used 
     *
     * @return bool true if the permissions are used
     */
    public function getUsed() {
        return $this->used;
    }
    
    
    /**
     * Returns the soap value of this changeset value (the timestamp)
     *
     * @return string The value of this artifact changeset value for Soap API
     */
    public function getSoapValue() {
        return $this->encapsulateRawSoapValue(implode(",", $this->getPerms()));
    }

    public function getRESTValue() {
        // Not implemented yet
        // Should reference a /groups/:id URI
    }

    /**
     * Returns the value of this changeset value (human readable)
     *
     * @return string The value of this artifact changeset value for the web interface
     */
    public function getValue() {
        return '';
    }
    
    /**
     * Returns diff between current perms and perms in param
     *
     * @param Tracker_Artifact_ChangesetValue_PermissionsOnArtifact $changeset_value the changeset value to compare
     *
     * @return string The difference between another $changeset_value, false if no differneces
     */
    public function diff($changeset_value, $format = 'html') {
        $previous = $changeset_value->getPerms();
        $next = $this->getPerms();
        $changes = false;
        if ($previous !== $next) {
            $removed_elements = array_diff($previous, $next);
            $removed_arr = array();
            foreach ($removed_elements as $removed_element) {                
                $removed_arr[] = $this->getUgroupLabel($removed_element);
            }
            $removed = implode(', ', $removed_arr);
            $added_elements = array_diff($next, $previous);
            $added_arr = array();
            foreach ($added_elements as $added_element) {
                $added_arr[] = $this->getUgroupLabel($added_element);
            }
            $added   = implode(', ', $added_arr);
            if (empty($next)) {
                $changes = ' '.$GLOBALS['Language']->getText('plugin_tracker_artifact','cleared');
            } else if (empty($previous)) {
                $changes = $GLOBALS['Language']->getText('plugin_tracker_artifact','set_to').' '. $added;
            } else if (count($previous) == 1 && count($next) == 1) {
                $changes = ' '.$GLOBALS['Language']->getText('plugin_tracker_artifact','changed_from'). ' '.$removed .' '.$GLOBALS['Language']->getText('plugin_tracker_artifact','to').' '.$added;
            } else {
                if ($removed) {
                    $changes = $removed .' '. $GLOBALS['Language']->getText('plugin_tracker_artifact','removed');
                }
                if ($added) {
                    if ($changes) {
                        $changes .= PHP_EOL;
                    }
                    $changes .= $added .' '. $GLOBALS['Language']->getText('plugin_tracker_artifact','added');
                }
            }
            
        }
        return $changes;
    }
    
    public function nodiff() {
        $next = $this->getPerms();
        $added_arr = array();
        foreach ($next as $element) {
                $added_arr[] = $this->getUgroupLabel($element);
        }
        $added = implode(', ', $added_arr);
        return ' '.$GLOBALS['Language']->getText('plugin_tracker_artifact','set_to').' '.$added;
    }
    
    protected function getDao() {
        return new UGroupDao(CodendiDataAccess::instance());
    }
    
    protected function getUgroupLabel($u_group) {
        $row = $this->getDao()->searchByUGroupId($u_group)->getRow();
        return util_translate_name_ugroup($row['name']);
    }
}
?>
