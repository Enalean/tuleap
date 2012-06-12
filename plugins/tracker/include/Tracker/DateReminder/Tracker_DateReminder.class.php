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

require_once(dirname(__FILE__).'/../FormElement/Tracker_FormElementFactory.class.php');
require_once(dirname(__FILE__).'/../FormElement/Tracker_FormElement_Field.class.php');
class Tracker_DateReminder {
    
    protected $reminderId;
    protected $trackerId;
    protected $fieldId;
    protected $ugroups;
    protected $notificationType;
    protected $distance;
    protected $status;

    /**
     * @var Tracker_FormElement_Field
     */
    protected $field = null;
    

   /**
    * Constructor of the class
    *
    * @param Integer $reminderId       Id of the reminder
    * @param Integer $trackerId        Id of the tracker
    * @param Integer $fieldId          Id of the field
    * @param String  $ugroups          List of ugroups to be notified
    * @param Integer $notificationType Befor or after the date value
    * @param Integer $distance         Distance from the date value
    * @param Integer $status           Status of the reminder
    *
    * @return Void
    */
    public function __construct($reminderId, $trackerId, $fieldId, $ugroups, $notificationType, $distance, $status) {
        $this->reminderId       = $reminderId;
        $this->trackerId        = $trackerId;
        $this->fieldId          = $fieldId;
        $this->ugroups          = $ugroups;
        $this->notificationType = $notificationType;
        $this->distance         = $distance;
        $this->status           = $status;
    }

    /**
     * Get reminder id
     *
     * @return String
     */
    public function getId() {
        return $this->reminderId;
    }

    /**
     * Set field
     *
     * @param Tracker_FormElement_Field $field Field
     *
     * @return Void
     */
    protected function setField(Tracker_FormElement_Field $field) {
        $this->field   = $field;
        $this->fieldId = $field->getId();
    }

    /**
     * Get tracker id of the reminder
     *
     * @return Integer
     */
    public function getTrackerId() {
        return $this->trackerId;
    }

    /**
     * Get field id of the reminder
     *
     * @return Integer
     */
    public function getFieldId() {
        return $this->fieldId;
    }

    /**
     * Get the date field
     *
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
     * Get the Notification Type of this reminder
     *
     * @return Integer
     */
    public function getNotificationType() {
        return $this->notificationType;
    }

    /**
     * Get the distance of this reminder
     *
     * @return Integer
     */
    public function getDistance() {
        return $this->distance;
    }

    /**
     * Get the notified ugroups ids of this reminder
     *
     * @return Mixed
     */
    public function getUgroups($asArray = false) {
        $ugroups = $this->ugroups;
        if ($asArray) {
            $ugroups = split('[,]', $this->ugroups);
            $ugroups = array_map('trim', $ugroups);
        }
        return $ugroups;
    }

    /**
     * Get the status of this reminder
     *
     * @return Integer
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * Set the tracker of this reminder
     *
     * @param Tracker $tracker The tracker
     *
     * @return Void
     */
    protected function setTracker($trackerId) {
        $this->trackerId = $trackerId;
    }
    
    /**
     * Set the Notification Type of this reminder
     *
     * @param Integer $notificationType The Notification Type
     *
     * @return Void
     */
    protected function setNotificationType($notificationType) {
        $this->notificationType = $notificationType;
    }

    /**
     * Set the Ugroup of this reminder
     *
     * @param String $ugroups The user groups to be notified
     *
     * @return Void
     */
    protected function setUgroups($ugroups) {
        $this->ugroups = $ugroups;
    }

    /**
     * Retrieve the recipient list for all ugroup_id's
     *
     * @return Array
     */
    public function getRecipients() {
        //@TODO retrieve members for the ugroups list
        $recipients    = array();
        $uGroupManager = new UGroupManager();
        $ugroups       = $this->getUgroups(true);
        foreach ($ugroups as $ugroupId) {
            if ($ugroupId < 100) {
                $um = UserManager::instance();
                $members = $uGroupManager->getDynamicUGroupsMembers($ugroupId, $this->getTracker()->getGroupId());
                if ($members && !$members->isError()) {
                    foreach ($members as $member) {
                        $user = $um->getUserById($member['user_id']);
                        $recipients[$user->getId()] = $user;
                    }
                }
            } else {
                $uGroup     = $uGroupManager->getById($ugroupId);
                $members    = $uGroup->getMembers();
                foreach ($members as $user) {
                    $recipients[$user->getId()] = $user;
                }
            }
        }
        return $recipients;
    }

    /**
     * Retrieve the reminder status as a string
     *
     * @return String
     */
    public function getReminderStatusLabel() {
        if ($this->getStatus() == 1) {
            $reminderStatusLabel = "enabled";
        } else {
            $reminderStatusLabel = "disabled";
        }
        return $reminderStatusLabel;
    }

    /**
     * Retrieve the reminder notification type as a string
     *
     * @return String
     */
    public function getNotificationTypeLabel() {
        if ($this->getNotificationType() == 1) {
            $notificationTypeLabel = "after";
        } else {
            $notificationTypeLabel = "before";
        }
        return $notificationTypeLabel;
    }

    /**
     * React when reminder is treated as a string
     *
     * @return String
     */
    public function __toString() {
        $fieldLabel  = ' "'.$this->getField()->name.'" ';
        $notificationTypeLabel = $this->getNotificationTypeLabel();
        //@TODO retrieve comma separated ugroups
        $ugroupsLabel   = '';
        $ugroupManager  = new UGroupManager();
        $ugroups        = explode(',', $this->ugroups);
        foreach ($ugroups as $ugroup) {
            $ugroupsLabel  .= ' "'.$ugroupManager->getById($ugroup)->getName().' "';
        }
        return $this->distance.' day(s) '.$notificationTypeLabel.$fieldLabel.' send an email to '.$ugroupsLabel;
    }
}

?>