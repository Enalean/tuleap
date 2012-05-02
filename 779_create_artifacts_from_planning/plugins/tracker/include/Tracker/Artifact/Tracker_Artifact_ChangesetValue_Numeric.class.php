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

require_once('Tracker_Artifact_ChangesetValue.class.php');

/**
 * Manage values in changeset for numeric fields
 */
abstract class Tracker_Artifact_ChangesetValue_Numeric extends Tracker_Artifact_ChangesetValue {
    
    /**
     * @var mixed (int or float)
     */
    protected $numeric;
    
    /**
     * Constructor
     *
     * @param Tracker_FormElement_Field_Numeric $field       The field of the value
     * @param boolean                           $has_changed If the changeset value has chnged from the previous one
     * @param numeric                           $numeric     The numeric
     */
    public function __construct($id, $field, $has_changed, $numeric) {
        parent::__construct($id, $field, $has_changed);
        $this->numeric = $numeric;
    }
    
    /**
     * Get the numeric value
     *
     * @return mixed (int or float) the Numeric
     */
    public function getNumeric() {
        return $this->numeric;
    }

    /**
     * Get the string value
     *
     * @return string
     */
    public function getValue() {
        return $this->numeric;
    }
    /**
     * Get the diff between this numeric value and the one passed in param
     *
     * @param Tracker_Artifact_ChangesetValue_Numeric $changeset_value the changeset value to compare
     *
     * @return string The difference between another $changeset_value, false if no differences
     */
    public function diff($changeset_value, $format = 'html') {
        $previous_numeric = $changeset_value->getValue();
        $next_numeric     = $this->getValue();
        if ($previous_numeric !== $next_numeric) {
            if ($previous_numeric === null) {
                return $GLOBALS['Language']->getText('plugin_tracker_artifact','set_to') . ' ' . $next_numeric;
            } elseif ($next_numeric === null) {
                return $GLOBALS['Language']->getText('plugin_tracker_artifact','cleared');
            } else {
                return $GLOBALS['Language']->getText('plugin_tracker_artifact','changed_from'). ' ' . $previous_numeric . ' ' . $GLOBALS['Language']->getText('plugin_tracker_artifact','to') . ' ' . $next_numeric;
            }
        }
        return false;
    }
    
     /**
     * Returns the "set to" for field added later
     *
     * @return string The sentence to add in changeset
     */
    public function nodiff() {
        if ($this->getNumeric() != 0) {
            return $GLOBALS['Language']->getText('plugin_tracker_artifact','set_to').' '.$this->getValue();
        }
    }
}

?>