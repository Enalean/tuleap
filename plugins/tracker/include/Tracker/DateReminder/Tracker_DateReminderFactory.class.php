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

require_once 'common/date/DateHelper.class.php';

class Tracker_DateReminderFactory {

    protected $tracker;

    /**
     * Constructor of the class
     *
     * @param Tracker $tracker Tracker associated to the manager
     *
     * @return Void
     */
    public function __construct(Tracker $tracker) {
        $this->tracker = $tracker;
        $this->csrf    = new CSRFSynchronizerToken(TRACKER_BASE_URL.'/?func=admin-notifications&tracker='.$this->tracker->id);
    }

    /**
     * Obtain the tracker associated to the manager
     *
     * @return Tracker
     */
    public function getTracker() {
        return $this->tracker;
    }

    /**
     * Retrieve date reminders for a given tracker
     *
     * @param boolean $allReminders Retrieve enabled and disabled reminders (optional)
     *
     * @return Tracker_DateReminder[]
     */
    public function getTrackerReminders($allReminders = false) {
        $reminders = array();
        $reminderDao = $this->getDao();
        if ($allReminders) {
            $dar = $reminderDao->getDateReminders($this->getTracker()->getId(), false);
        } else {
            $dar = $reminderDao->getDateReminders($this->getTracker()->getId());
        }
        if ($dar && !$dar->isError()) {
            foreach ($dar as $row) {
                $reminders[] = $this->getInstanceFromRow($row);
            }
        }
        return $reminders;
    }

    /**
     * Add new reminder
     *
     * @param HTTPRequest $request Request object
     *
     * @return Boolean
     */
    public function addNewReminder(HTTPRequest $request) {
        $this->csrf->check();
        $reminderRenderer = new Tracker_DateReminderRenderer($this->getTracker());
        try {
            $trackerId        = $reminderRenderer->validateTrackerId($request);
            $fieldId          = $reminderRenderer->validateFieldId($request);
            $notificationType = $reminderRenderer->validateNotificationType($request);
            $distance         = $reminderRenderer->validateDistance($request);
            $notified         = $reminderRenderer->scindReminderNotifiedPeople($request);
            $ugroups          = $reminderRenderer->validateReminderUgroups($notified[0]);
            $roles            = $reminderRenderer->validateReminderRoles($notified[1]);
            if (!empty ($ugroups)) {
                $ugroups          = join(",", $ugroups);
            } else {
                $ugroups = "";
            }
            $this->checkDuplicatedReminders($trackerId, $fieldId, $notificationType, $distance);
            $this->isReminderBeforeOpenDate($fieldId, $notificationType);
        } catch (Tracker_DateReminderException $e) {
            $GLOBALS['Response']->addFeedback('error', $e->getMessage());
            $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?func=admin-notifications&tracker='.$this->getTracker()->getId());
        }
        $reminder = $this->getDao()->addDateReminder($trackerId, $fieldId, $ugroups, $roles, $notificationType, $distance);
        if ($reminder) {
            $roles = implode("," , $roles);
            $historyDao = new ProjectHistoryDao(CodendiDataAccess::instance());
            $historyDao->groupAddHistory("tracker_date_reminder_add", $this->getTracker()->getName().":".$fieldId, $this->getTracker()->getGroupId(), array($distance.' Day(s), Type: '.$notificationType.' ProjectUGroup(s): '.$ugroups. 'Tracker Role(s): '.$roles));
            return $reminder;
        } else {
            $errorMessage = $GLOBALS['Language']->getText('plugin_tracker_date_reminder','tracker_date_reminder_add_failure', array($trackerId, $fieldId));
            throw new Tracker_DateReminderException($errorMessage);
        }
    }

    /**
     * Throws an excpetion when an enabled date reminder having same params (field, notification Type and the distance) already exist
     *
     * @param Integer $trackerId        Id of the tracker
     * @param Integer $fieldId          Id of the date field
     * @param Integer $notificationType 0 if before, 1 if after the value of the date field
     * @param Integer $distance         Distance from the value of the date fiels
     * @param Integer $reminderId       Id of the reminder if it is an updated one Else 0
     *
     * @return Void
     */
    protected function checkDuplicatedReminders($trackerId, $fieldId, $notificationType, $distance, $reminderId = 0) {
        $dupilcatedReminders = $this->getDao()->findReminders($trackerId, $fieldId, $notificationType, $distance, $reminderId);
        if ($dupilcatedReminders && !$dupilcatedReminders->isError() && $dupilcatedReminders->rowCount() > 0) {
            $errorMessage = $GLOBALS['Language']->getText('project_admin_utils','tracker_date_reminder_duplicated');
            throw new Tracker_DateReminderException($errorMessage);
        }
    }

    /**
     * Throws an excpetion when an notification type is before 'submitted on' date field
     *
     * @param Integer $fieldId          Id of the date field
     * @param Integer $notificationType 0 if before, 1 if after the value of the date field
     *
     * @return Void
     */
    protected function isReminderBeforeOpenDate($fieldId, $notificationType) {
        $tff              = Tracker_FormElementFactory::instance();
        $trackerDateField = $tff->getFieldById($fieldId);
        $fieldType        = $tff->getType($trackerDateField);
        if ($fieldType == 'subon' && $notificationType == 0) {
            $errorMessage = $GLOBALS['Language']->getText('project_admin_utils','tracker_date_reminder_before_submittedOn');
            throw new Tracker_DateReminderException($errorMessage);
        }
    }

    /**
     * Edit a given date reminder
     *
     * @param HTTPRequest $request Reminder edit request
     *
     * @return Boolean
     */
    public function editTrackerReminder($request) {
        $this->csrf->check();
        $reminderRenderer   = new Tracker_DateReminderRenderer($this->getTracker());
        try {
            $reminderId       = $reminderRenderer->validateReminderId($request);
            $notificationType = $reminderRenderer->validateNotificationType($request);
            $distance         = $reminderRenderer->validateDistance($request);
            $status           = $reminderRenderer->validateStatus($request);
            $notified         = $reminderRenderer->scindReminderNotifiedPeople($request);
            $ugroups          = $reminderRenderer->validateReminderUgroups($notified[0]);
            $roles            = $reminderRenderer->validateReminderRoles($notified[1]);
            if (!empty($ugroups)) {
                $ugroups      = join(",", $ugroups);
            } else {
                $ugroups = "";
            }
            $fieldId          = $reminderRenderer->validateFieldId($request);
            if ($status == 1) {
                $this->checkDuplicatedReminders($this->getTracker()->getId(), $fieldId, $notificationType, $distance, $reminderId);
                $this->isReminderBeforeOpenDate($fieldId, $notificationType);
            }
        } catch (Tracker_DateReminderException $e) {
            $GLOBALS['Response']->addFeedback('error', $e->getMessage());
            $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?func=admin-notifications&tracker='.$this->getTracker()->id);
        }
        $updateReminder = $this->getDao()->updateDateReminder($reminderId, $ugroups, $roles, $notificationType, $distance, $status);
        if ($updateReminder) {
            $roles = implode("," , $roles);
            $historyDao = new ProjectHistoryDao(CodendiDataAccess::instance());
            $historyDao->groupAddHistory("tracker_date_reminder_edit", $this->getTracker()->getName().":".$reminderId, $this->getTracker()->getGroupId(), array("Id: ".$reminderId.", Type: ".$notificationType.", ProjectUGroup(s): ".$ugroups.", Tracker Role(s): ".$roles.", Day(s): ".$distance.", Status: ".$status));
            return $updateReminder;
        } else {
            $errorMessage = $GLOBALS['Language']->getText('plugin_tracker_date_reminder','tracker_date_reminder_update_failure', array($reminderId));
            throw new Tracker_DateReminderException($errorMessage);
        }
    }

    /**
     * Build a reminder instance
     *
     * @param array $row The data describing the reminder
     *
     * @return Tracker_DateReminder
     */
    public function getInstanceFromRow($row) {
        $roles = array();
        $dar = $this->getDao()->getRolesByReminderId($row['reminder_id']);
        if ($dar && !$dar->isError() && $dar->rowCount() >0) {
            foreach ($dar as $da) {
                switch ($da['role_id']) {
                    case "1":
                        $roles[] = new Tracker_DateReminder_Role_Submitter();
                        break;

                    case "2":
                        $roles[] = new Tracker_DateReminder_Role_Assignee();
                        break;

                    case "3":
                        $roles[] = new Tracker_DateReminder_Role_Commenter();
                        break;

                    default:
                    break;
                }
            }
        }
        return new Tracker_DateReminder($row['reminder_id'],
                                        $row['tracker_id'],
                                        $row['field_id'],
                                        $row['ugroups'],
                                        $roles,
                                        $row['notification_type'],
                                        $row['distance'],
                                        $row['status']);
    }

    /**
     * Get the Tracker_DateReminder dao
     *
     * @return Tracker_DateReminderDao
     */
    protected function getDao() {
        return new Tracker_DateReminderDao();
    }

    /**
     * Get the reminder
     *
     * @param Integer $reminderId The reminder id
     *
     * @return Tracker_DateReminder
     */
    public function getReminder($reminderId) {
        if ($row = $this->getDao()->searchById($reminderId)->getRow()) {
            return $this->getInstanceFromRow($row);
        }
        return null;
    }

    /**
     * Get an instance of UserManager
     *
     * @return UserManager
     */
    public function getUserManager() {
        return UserManager::instance();
    }

    /**
     * Delete a given date reminder
     *
     * @param Integer $reminderId Id of reminder
     *
     * @return Boolean
     */
    public function deleteTrackerReminder($reminderId) {
        if(is_numeric($reminderId)) {
            $deleteReminder = $this->getDao()->deleteReminder($reminderId);
            if ($deleteReminder) {
                $historyDao = new ProjectHistoryDao(CodendiDataAccess::instance());
                $historyDao->groupAddHistory("tracker_date_reminder_delete", $this->tracker->getName(), $this->tracker->getGroupId(), array("Id: ".$reminderId));
                return $deleteReminder;
            } else {
                $errorMessage = $GLOBALS['Language']->getText('plugin_tracker_date_reminder','tracker_date_reminder_delete_failure', array($reminderId));
                throw new Tracker_DateReminderException($errorMessage);
            }
        } else {
            $errorMessage = $GLOBALS['Language']->getText('project_admin_utils','tracker_date_reminder_invalid_reminder', array($reminderId));
            throw new Tracker_DateReminderException($errorMessage);
        }
    }

}

?>