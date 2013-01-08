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
 * Manage values in changeset for date fields
 */
class Tracker_Artifact_ChangesetValue_Date extends Tracker_Artifact_ChangesetValue {
    
    /**
     * @var int
     */
    protected $timestamp;
    
    /**
     * Constructor
     *
     * @param Tracker_FormElement_Field_Date $field       The field of the value
     * @param boolean                        $has_changed If the changeset value has chnged from the previous one
     * @param int                            $timestamp   The date
     */
    public function __construct($id, $field, $has_changed, $timestamp) {
        parent::__construct($id, $field, $has_changed);
        $this->timestamp = $timestamp;
    }
    
    /**
     * Get timestamp of this changeset value date
     *
     * @return int the timestamp, or null if date is null (none)
     */
    public function getTimestamp() {
        return $this->timestamp;
    }
    
    /**
     * Get human-readable representation of the date
     *
     * @return string the human-readable representation of the date, or '' if date is null(none)
     */
    public function getDate() {
        return $this->formatDate($this->timestamp);
    }
    
    /**
     * Format the timestamp in human readable date
     *
     * @param int $timestamp The date
     *
     * @return string the date in the format Y-m-d (user pref?), or '' if date is null (none)
     */
    protected function formatDate($timestamp) {
        if ($timestamp === null) {
            return '';
        } else {
            return date($GLOBALS['Language']->getText('system', 'datefmt_short'), $timestamp);
        }
    }
    
    /**
     * Returns the soap value of this changeset value (the timestamp)
     *
     * @return string The value of this artifact changeset value for Soap API
     */
    public function getSoapValue() {
        return (string)$this->getTimestamp();
    }
    
    /**
     * Returns the value of this changeset value (human readable)
     *
     * @return string The value of this artifact changeset value for the web interface, or '' if date is null (none)
     */
    public function getValue() {
        return $this->getDate();
    }
    
    /**
     * Returns diff between current date and date in param
     *
     * @param Tracker_Artifact_ChangesetValue_Date $changeset_value the changeset value to compare
     *
     * @return string The difference between another $changeset_value, false if no differneces
     */
    public function diff($changeset_value, $format = 'html') {
        $next_date     = $this->getDate();
        if ($changeset_value->getTimestamp() != 0) {
            $previous_date = $changeset_value->getDate();
            if ($previous_date !== $next_date) {
                if ($next_date === '') {
                    return $GLOBALS['Language']->getText('plugin_tracker_artifact','cleared');
                } else {
                    return $GLOBALS['Language']->getText('plugin_tracker_artifact','changed_from'). ' '.$previous_date .' '.$GLOBALS['Language']->getText('plugin_tracker_artifact','to').' '.$next_date;
                }
            }
        } else {
            return $GLOBALS['Language']->getText('plugin_tracker_artifact','set_to').' '.$next_date;
        }
        return false;
    }
    
    /**
     * Returns the "set to" date for field added later
     *
     * @return string The sentence to add in changeset
     */
    public function nodiff() {
        if ($this->getTimestamp() !=0) {
            $next_date     = $this->getDate();
            return $GLOBALS['Language']->getText('plugin_tracker_artifact','set_to').' '.$next_date;
        }
    }
    
}
?>
