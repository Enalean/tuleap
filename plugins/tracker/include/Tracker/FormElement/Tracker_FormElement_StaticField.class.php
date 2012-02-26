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

require_once('Tracker_FormElement.class.php');

/**
 * The base class for static fields in trackers.
 * Static Fields are not real fields, as they don't have a specific value for each artifact.
 * The value can be updated, but is the same for every artifact. 
 */
abstract class Tracker_FormElement_StaticField extends Tracker_FormElement {
    
	/**
     * getLabel - the label of this Tracker_FormElement_Line_Break
     * The tracker label can be internationalized.
     * To do this, fill the name field with the ad-hoc format.
     *
     * @return string label, the name if the name is not internationalized, or the localized text if so
     */
    function getLabel() {
        global $Language;
        if ($this->isLabelMustBeLocalized()) {
            return $Language->getText('plugin_tracker_common_staticfield', $this->label);
        } else {
            return $this->label;
        }
    }
    
    /**
     * Returns if the static field name must be localized or not.
     * The 'form element static field' name must be localized if the name looks like staticfield_{$field_id}_lbl_key
     *
     * @return true if the static field name must be localized, false otherwise.
     */
    function isLabelMustBeLocalized() {
        $pattern = "staticfield_(.*)_lbl_key";
        return ereg($pattern, $this->label);
    }
    
    /**
     * getDescriptionText - the text of the description of this Tracker_FormElement_FieldSet
     * The tracker descripiton can be internationalized.
     * To do this, fill the description field with the ad-hoc format.
     *
     * @return string description, the description text if the description is not internationalized, or the localized text if so
     */
    function getDescriptionText() {
        global $Language;
        if ($this->isDescriptionMustBeLocalized()) {
            return $Language->getText('plugin_tracker_common_staticfield', $this->description);
        } else {
            return $this->description;
        }
    }
    
    /**
     * Returns if the static field description must be localized or not.
     * The static field description must be localized if the name looks like staticfield_{$field_id}_desc_key
     *
     * @return true if the static field description must be localized, false otherwise.
     */
    function isDescriptionMustBeLocalized() {
        $pattern = "staticfield_(.*)_desc_key";
        return ereg($pattern, $this->description);
    }
    
    
	// TODO : remove these functions (no need for that kind of "fields"
    public function fetchAddCriteria($used, $prefix = '') {
        return null;
    }
    
    public function fetchAddColumn($used, $prefix = '') {
        return null;
    }
    
    public function fetchAddTooltip($used, $prefix = '') {
        return null;
    }
    
    /**
     * Fetch the element for the update artifact form
     *
     * @param Tracker_Artifact $artifact
     *
     * @return string html
     */
    public function fetchArtifact(Tracker_Artifact $artifact) {
        return $this->fetchReadOnly();
    }
    
    /**
     * Fetch the element for the submit new artifact form
     *
     * @return string html
     */
    public function fetchSubmit() {
        return $this->fetchReadOnly();
    }

    /**
     * Fetch the element for the submit new artifact form
     *
     * @return string html
     */
    public function fetchSubmitMasschange() {
        return $this->fetchReadOnly();
    }
    /**
     * Say if the field is updateable
     *
     * @return bool
     */
    public function isUpdateable() {
        return false;
    }
    
    /**
     * Say if the field is submitable
     *
     * @return bool
     */
    public function isSubmitable() {
        return false;
    }
    
    /**
     * Is the field can be set as unused?
     * You can't set a field unused if it is used in the tracker
     * This method is to prevent tracker inconsistency
     *
     * @return boolean returns true if the field can be unused, false otherwise
     */
    public function canBeUnused() {
        return true;
    }
    
    /** 
     * return true if user has Read or Update permission on this field
     * 
     * @param User $user The user. if not given or null take the current user
     *
     * @return bool
     */ 
    public function userCanRead(User $user = null) {
        return true;
    }
    
    protected abstract function fetchReadOnly();
}

?>