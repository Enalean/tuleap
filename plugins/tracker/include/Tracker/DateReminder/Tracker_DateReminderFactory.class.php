<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

class Tracker_DateReminderFactory
{

    protected $tracker;
    /**
     * @var Tracker_DateReminderRenderer
     */
    private $date_reminder_renderer;

    /**
     * Constructor of the class
     *
     * @param Tracker $tracker Tracker associated to the manager
     *
     * @return Void
     */
    public function __construct(Tracker $tracker, Tracker_DateReminderRenderer $date_reminder_renderer)
    {
        $this->tracker                = $tracker;
        $this->date_reminder_renderer = $date_reminder_renderer;
    }

    /**
     * Obtain the tracker associated to the manager
     *
     * @return Tracker
     */
    public function getTracker()
    {
        return $this->tracker;
    }

    /**
     * Retrieve date reminders for a given tracker
     *
     * @param bool $allReminders Retrieve enabled and disabled reminders (optional)
     *
     * @return Tracker_DateReminder[]
     */
    public function getTrackerReminders($allReminders = false)
    {
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
     * @return bool
     */
    public function addNewReminder(HTTPRequest $request)
    {
        try {
            $fieldId          = $this->date_reminder_renderer->validateFieldId($request);
            $notificationType = $this->date_reminder_renderer->validateNotificationType($request);
            $distance         = $this->date_reminder_renderer->validateDistance($request);
            $notified         = $this->date_reminder_renderer->scindReminderNotifiedPeople($request);
            $ugroups          = $this->date_reminder_renderer->validateReminderUgroups($notified[0]);
            $roles            = $this->date_reminder_renderer->validateReminderRoles($notified[1]);
            if (!empty($ugroups)) {
                $ugroups          = join(",", $ugroups);
            } else {
                $ugroups = "";
            }
            $this->checkDuplicatedReminders($fieldId, $notificationType, $distance);
            $this->isReminderBeforeOpenDate($fieldId, $notificationType);
        } catch (Tracker_DateReminderException $e) {
            $GLOBALS['Response']->addFeedback('error', $e->getMessage());
            $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/notifications/' . urlencode((string) $this->getTracker()->getId()) . '/');
            exit();
        }
        $reminder = $this->getDao()->addDateReminder($this->getTracker()->getId(), $fieldId, $ugroups, $roles, $notificationType, $distance);
        if ($reminder) {
            $roles = implode(",", $roles);
            $historyDao = new ProjectHistoryDao(CodendiDataAccess::instance());
            $historyDao->groupAddHistory("tracker_date_reminder_add", $this->getTracker()->getName() . ":" . $fieldId, $this->getTracker()->getGroupId(), array($distance . ' Day(s), Type: ' . $notificationType . ' ProjectUGroup(s): ' . $ugroups . 'Tracker Role(s): ' . $roles));
            return $reminder;
        } else {
            $errorMessage = $GLOBALS['Language']->getText(
                'plugin_tracker_date_reminder',
                'tracker_date_reminder_add_failure',
                [$this->getTracker()->getId(), $fieldId]
            );
            throw new Tracker_DateReminderException($errorMessage);
        }
    }

    /**
     * Throws an excpetion when an enabled date reminder having same params (field, notification Type and the distance) already exist
     *
     * @param int $fieldId Id of the date field
     * @param int $notificationType 0 if before, 1 if after the value of the date field
     * @param int $distance Distance from the value of the date fiels
     * @param int $reminderId Id of the reminder if it is an updated one Else 0
     *
     * @return Void
     */
    protected function checkDuplicatedReminders($fieldId, $notificationType, $distance, $reminderId = 0)
    {
        $dupilcatedReminders = $this->getDao()->findReminders($this->getTracker()->getId(), $fieldId, $notificationType, $distance, $reminderId);
        if ($dupilcatedReminders && !$dupilcatedReminders->isError() && $dupilcatedReminders->rowCount() > 0) {
            $errorMessage = $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_duplicated');
            throw new Tracker_DateReminderException($errorMessage);
        }
    }

    /**
     * Throws an excpetion when an notification type is before 'submitted on' date field
     *
     * @param int $fieldId Id of the date field
     * @param int $notificationType 0 if before, 1 if after the value of the date field
     *
     * @return Void
     */
    protected function isReminderBeforeOpenDate($fieldId, $notificationType)
    {
        $tff              = Tracker_FormElementFactory::instance();
        $trackerDateField = $tff->getFieldById($fieldId);
        if ($trackerDateField === null) {
            return;
        }
        $fieldType        = $tff->getType($trackerDateField);
        if ($fieldType == 'subon' && $notificationType == 0) {
            $errorMessage = $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_before_submittedOn');
            throw new Tracker_DateReminderException($errorMessage);
        }
    }

    /**
     * Edit a given date reminder
     *
     * @param HTTPRequest $request Reminder edit request
     *
     * @return bool
     */
    public function editTrackerReminder(Tracker_DateReminder $reminder, HTTPRequest $request)
    {
        try {
            $notificationType = $this->date_reminder_renderer->validateNotificationType($request);
            $distance         = $this->date_reminder_renderer->validateDistance($request);
            $status           = $this->date_reminder_renderer->validateStatus($request);
            $notified         = $this->date_reminder_renderer->scindReminderNotifiedPeople($request);
            $ugroups          = $this->date_reminder_renderer->validateReminderUgroups($notified[0]);
            $roles            = $this->date_reminder_renderer->validateReminderRoles($notified[1]);
            if (!empty($ugroups)) {
                $ugroups      = join(",", $ugroups);
            } else {
                $ugroups = "";
            }
            $fieldId          = $this->date_reminder_renderer->validateFieldId($request);
            if ($status == 1) {
                $this->checkDuplicatedReminders($fieldId, $notificationType, $distance, $reminder->getId());
                $this->isReminderBeforeOpenDate($fieldId, $notificationType);
            }
        } catch (Tracker_DateReminderException $e) {
            $GLOBALS['Response']->addFeedback('error', $e->getMessage());
            $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/notifications/' . urlencode((string) $this->getTracker()->getId()) . '/');
            exit();
        }
        $updateReminder = $this->getDao()->updateDateReminder($reminder->getId(), $ugroups, $roles, $notificationType, $distance, $status);
        if ($updateReminder) {
            $roles = implode(",", $roles);
            $historyDao = new ProjectHistoryDao(CodendiDataAccess::instance());
            $historyDao->groupAddHistory("tracker_date_reminder_edit", $this->getTracker()->getName() . ":" . $reminder->getId(), $this->getTracker()->getGroupId(), array("Id: " . $reminderId . ", Type: " . $notificationType . ", ProjectUGroup(s): " . $ugroups . ", Tracker Role(s): " . $roles . ", Day(s): " . $distance . ", Status: " . $status));
            return $updateReminder;
        } else {
            $errorMessage = $GLOBALS['Language']->getText('plugin_tracker_date_reminder', 'tracker_date_reminder_update_failure', array($reminder->getId()));
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
    public function getInstanceFromRow($row)
    {
        $roles = array();
        $dar = $this->getDao()->getRolesByReminderId($row['reminder_id']);
        if ($dar && !$dar->isError() && $dar->rowCount() > 0) {
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
        return new Tracker_DateReminder(
            $row['reminder_id'],
            $row['tracker_id'],
            $row['field_id'],
            $row['ugroups'],
            $roles,
            $row['notification_type'],
            $row['distance'],
            $row['status']
        );
    }

    /**
     * Get the Tracker_DateReminder dao
     *
     * @return Tracker_DateReminderDao
     */
    protected function getDao()
    {
        return new Tracker_DateReminderDao();
    }

    /**
     * Get the reminder
     *
     * @param int $reminderId The reminder id
     *
     * @return Tracker_DateReminder
     */
    public function getReminder($reminderId)
    {
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
    public function getUserManager()
    {
        return UserManager::instance();
    }

    public function deleteTrackerReminder(Tracker_DateReminder $reminder)
    {
        $deleteReminder = $this->getDao()->deleteReminder($reminder->getId());
        if ($deleteReminder) {
            $historyDao = new ProjectHistoryDao(CodendiDataAccess::instance());
            $historyDao->groupAddHistory(
                'tracker_date_reminder_delete',
                $this->tracker->getName(),
                $this->tracker->getGroupId(),
                ['Id: ' . $reminder->getId()]
            );
            return;
        }
        throw new Tracker_DateReminderException(
            $GLOBALS['Language']->getText(
                'plugin_tracker_date_reminder',
                'tracker_date_reminder_delete_failure',
                [$reminder->getId()]
            )
        );
    }
}
