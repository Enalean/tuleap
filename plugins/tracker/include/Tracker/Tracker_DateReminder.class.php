<?php
/**
 * Copyright (c) STMicroelectronics 2012. All rights reserved
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
 
class Tracker_DateReminder {
    
    public $reminderId;
    public $trackerId;
    public $fieldId;
    public $ugroupId;

    /**
     * @var Tracker_FormElement_Field
     */
    protected $field = null;
    

   
    public function __construct($reminderId, $trackerId, $fieldId, $ugroupId) {
        $this->reminderId      = $reminderId;
        $this->trackerId = $trackerId;
        $this->fieldId   = $fieldId;
        $this->ugroupId   = $ugroupId;
    }

    /**
     * Set field
     *
     * @param Tracker_FormElement_Field $field Field
     */
    public function setField(Tracker_FormElement_Field $field) {
        $this->field    = $field;
        $this->fieldId = $field->getId();
    }
    
    /**
     * @return string
     */
    public function getTrackerId() {
        return $this->trackerIid;
    }
    
    /**
     * @return string
     */
    public function getFieldId() {
        return $this->field_id;
    }
    
    /**
     * @return Tracker_FormElement_Field
     */
    public function getField() {
        if (!$this->field) {
            $this->field = Tracker_FormElementFactory::instance()->getUsedFormElementById($this->getFieldId());
        }
        return $this->field;
    }

    /**
     * Return the tracker of this reminder
     *
     * @return Tracker
     */
    public function getTracker() {
        return TrackerFactory::instance()->getTrackerByid($this->trackerId);
    }

    /**
     * Set the tracker of this reminder
     *
     * @param Tracker $tracker The tracker
     *
     * @return void
     */
    public function setTracker($trackerId) {
        $this->trackerId = $trackerId;
    }
    
}
?>