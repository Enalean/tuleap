<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

class Tracker_DateReminder
{

    public const BEFORE = 0;
    public const AFTER  = 1;

    public const DISABLED = 0;
    public const ENABLED  = 1;

    protected $reminderId;
    protected $trackerId;
    protected $fieldId;
    protected $ugroups;
    protected $roles;
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
    * @param int $reminderId Id of the reminder
    * @param int $trackerId Id of the tracker
    * @param int $fieldId Id of the field
    * @param String                       $ugroups          List of ugroups to be notified
    * @param Tracker_DateReminder_Role[]  $roles            Array of tracker predifined roles to be notified
    * @param int $notificationType Before or after the date value
    * @param int $distance Distance from the date value
    * @param int $status Status of the reminder
    *
    * @return Void
    */
    public function __construct($reminderId, $trackerId, $fieldId, $ugroups, $roles, $notificationType, $distance, $status)
    {
        $this->reminderId       = $reminderId;
        $this->trackerId        = $trackerId;
        $this->fieldId          = $fieldId;
        $this->ugroups          = $ugroups;
        $this->roles            = $roles;
        $this->notificationType = $notificationType;
        $this->distance         = $distance;
        $this->status           = $status;
    }

    /**
     * Get reminder id
     *
     * @return String
     */
    public function getId()
    {
        return $this->reminderId;
    }

    /**
     * Set field
     *
     * @param Tracker_FormElement_Field $field Field
     *
     * @return Void
     */
    protected function setField(Tracker_FormElement_Field $field)
    {
        $this->field   = $field;
        $this->fieldId = $field->getId();
    }

    /**
     * Get tracker id of the reminder
     *
     */
    public function getTrackerId(): int
    {
        return $this->trackerId;
    }

    /**
     * Get field id of the reminder
     *
     * @return int
     */
    public function getFieldId()
    {
        return $this->fieldId;
    }

    /**
     * Get the date field
     *
     * @return Tracker_FormElement_Field
     */
    public function getField()
    {
        if (!$this->field) {
            $this->field = Tracker_FormElementFactory::instance()->getUsedFormElementById($this->getFieldId());
        }
        return $this->field;
    }

    /**
     * Return the tracker of this reminder
     *
     * @return Tracker|null
     */
    public function getTracker()
    {
        return TrackerFactory::instance()->getTrackerByid($this->getTrackerId());
    }

    /**
     * Get the Notification Type of this reminder
     *
     * @return int
     */
    public function getNotificationType()
    {
        return $this->notificationType;
    }

    /**
     * Get the distance of this reminder
     *
     * @return int
     */
    public function getDistance()
    {
        return $this->distance;
    }

    /**
     * Get the notified ugroups ids of this reminder
     *
     * @param bool $asArray Return an array if true and a string otherwise
     *
     * @return Mixed
     */
    public function getUgroups($asArray = false)
    {
        $ugroups = $this->ugroups;
        if ($asArray) {
            $ugroups = explode(',', $this->ugroups);
            $ugroups = array_map('trim', $ugroups);
        }
        return $ugroups;
    }

    /**
     * Get the notified tracker roles of this reminder
     *
     * @return Array
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Get the status of this reminder
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set the tracker of this reminder
     *
     * @param int $trackerId Id of the tracker
     *
     * @return Void
     */
    protected function setTracker($trackerId)
    {
        $this->trackerId = $trackerId;
    }

    /**
     * Set the Notification Type of this reminder
     *
     * @param int $notificationType The Notification Type
     *
     * @return Void
     */
    protected function setNotificationType($notificationType)
    {
        $this->notificationType = $notificationType;
    }

    /**
     * Set the ProjectUGroup of this reminder
     *
     * @param String $ugroups The user groups to be notified
     *
     * @return Void
     */
    protected function setUgroups($ugroups)
    {
        $this->ugroups = $ugroups;
    }

    /**
     * Set the roles to be notified for this reminder
     *
     * @param Array $roles The roles to be notified
     *
     * @return Void
     */
    protected function setRoles($roles)
    {
        $this->roles = $roles;
    }

    /**
     * Retrieve the recipient list for all ugroup_id and tracker roles
     *
     * @param Tracker_Artifact $artifact  Artifact
     *
     * @return Array
     */
    public function getRecipients(Tracker_Artifact $artifact)
    {
        $recipients    = array();
        $ugroups       = $this->getUgroups(true);
        $roles         = $this->getRoles();
        if (!empty($ugroups)) {
            $recipients = array_merge($recipients, $this->getRecipientsFromUgroups());
        }

        if (!empty($roles)) {
            $recipients = array_merge($recipients, $this->getRecipientsFromRoles($artifact));
        }

        return $recipients;
    }

    /**
     * Retrieve the recipient list for all ugroup_id
     *
     * @return Array
     */
    private function getRecipientsFromUgroups()
    {
        $recipients = array();
        $uGroupManager = new UGroupManager();
        $ugroups       = $this->getUgroups(true);
        foreach ($ugroups as $ugroupId) {
            if ($ugroupId < 100) {
                $tracker = $this->getTracker();
                if ($tracker === null) {
                    throw new RuntimeException('Tracker does not exist');
                }
                $members = $uGroupManager->getDynamicUGroupsMembers($ugroupId, $tracker->getGroupId());
            } else {
                $uGroup  = $uGroupManager->getById($ugroupId);
                $members = $uGroup->getMembers();
            }
            foreach ($members as $user) {
                $recipients[$user->getId()] = $user;
            }
        }
        return $recipients;
    }

    /**
     * Retrieve the recipient list for Tracker Roles
     *
     * @param Tracker_Artifact $artifact  Artifact
     *
     * @return Array
     */
    private function getRecipientsFromRoles(Tracker_Artifact $artifact)
    {
        $recipients = array();
        $roles      = $this->getRoles();
        foreach ($roles as $userRole) {
            $recipients = array_merge($recipients, $userRole->getRecipientsFromArtifact($artifact));
        }
        return $recipients;
    }

    /**
     * Retrieve the reminder status as a string
     *
     * @return String
     */
    public function getReminderStatusLabel()
    {
        if ($this->getStatus() == self::ENABLED) {
            $reminderStatusLabel = $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_enabled');
        } else {
            $reminderStatusLabel = $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_disabled');
        }
        return $reminderStatusLabel;
    }

    /**
     * Retrieve the reminder notification type as a string
     *
     * @return String
     */
    public function getNotificationTypeLabel()
    {
        if ($this->getNotificationType() == self::AFTER) {
            $notificationTypeLabel = $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_after');
        } else {
            $notificationTypeLabel = $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_before');
        }
        return $notificationTypeLabel;
    }

    /**
     * Retrieve the reminder notified ugroups as string
     *
     * @return String
     */
    public function getUgroupsLabel()
    {
        $ugroupsLabel   = '';
        $ugroupManager  = $this->getUGroupManager();
        $ugroups        = explode(',', $this->ugroups);
        if (!empty($ugroups)) {
            foreach ($ugroups as $ugroup) {
                $ugroupsLabel  .= ' "' . util_translate_name_ugroup($ugroupManager->getById($ugroup)->getName()) . ' "';
            }
        }
        return $ugroupsLabel;
    }

    /**
     * Get the reminder notified tracker role label
     *
     * @return String
     */
    public function getRolesLabel()
    {
        $rolesLabel   = '';
        $roles        = $this->getRoles();
        foreach ($roles as $role) {
            $rolesLabel  .= ' "' . $role->getLabel() . ' "';
        }
        return $rolesLabel;
    }

    /**
     * Retreive The date Field value
     *
     * @param Tracker_Artifact $artifact The artifact
     *
     * @return date
     */
    public function getFieldValue(Tracker_Artifact $artifact)
    {
        $field = $this->getField();
        return $field->getLastValue($artifact);
    }

    /**
     * Wrapper for UGroupManager
     *
     * @return UGroupManager
     */
    protected function getUGroupManager()
    {
        return new UGroupManager();
    }
}
