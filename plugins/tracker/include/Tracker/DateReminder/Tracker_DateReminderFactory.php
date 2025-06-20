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

use Tuleap\Tracker\DateReminder\DateReminderDao;
use Tuleap\Tracker\Tracker;

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
    public function __construct(
        Tracker $tracker,
        Tracker_DateReminderRenderer $date_reminder_renderer,
        private DateReminderDao $date_reminder_dao,
    ) {
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
    public function getTrackerReminders($allReminders = false): array
    {
        $reminders = [];
        if ($allReminders) {
            $reminder_rows = $this->date_reminder_dao->getAllDateReminders($this->getTracker()->getId());
        } else {
            $reminder_rows = $this->date_reminder_dao->getActiveDateReminders($this->getTracker()->getId());
        }

        foreach ($reminder_rows as $row) {
            $reminders[] = $this->getInstanceFromRow($row);
        }

        return $reminders;
    }

    public function addNewReminder(HTTPRequest $request): bool
    {
        try {
            $fieldId                 = $this->date_reminder_renderer->validateFieldId($request);
            $notificationType        = $this->date_reminder_renderer->validateNotificationType($request);
            $distance                = $this->date_reminder_renderer->validateDistance($request);
            $notified                = $this->date_reminder_renderer->scindReminderNotifiedPeople($request);
            $ugroups                 = $this->date_reminder_renderer->validateReminderUgroups($notified[0]);
            $roles                   = $this->date_reminder_renderer->validateReminderRoles($notified[1]);
            $notify_closed_artifacts = $this->date_reminder_renderer->validateNotifyClosedArtifacts($request);
            if (! empty($ugroups)) {
                $ugroups = join(',', $ugroups);
            } else {
                $ugroups = '';
            }
            $this->checkDuplicatedRemindersAtCreation($fieldId, $notificationType, $distance);
            $this->isReminderBeforeOpenDate($fieldId, $notificationType);
        } catch (Tracker_DateReminderException $e) {
            $GLOBALS['Response']->addFeedback('error', $e->getMessage());
            $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/notifications/' . urlencode((string) $this->getTracker()->getId()) . '/');
            exit();
        }
        $reminder = $this->date_reminder_dao->addDateReminder(
            $this->getTracker()->getId(),
            $fieldId,
            $ugroups,
            $roles,
            $notificationType,
            $distance,
            (bool) $notify_closed_artifacts
        );
        if ($reminder) {
            $roles      = implode(',', $roles);
            $historyDao = new ProjectHistoryDao();
            $historyDao->groupAddHistory(
                'tracker_date_reminder_add',
                $this->getTracker()->getName() . ':' . $fieldId,
                $this->getTracker()->getGroupId(),
                [$distance . ' Day(s), Type: ' . $notificationType . ' ProjectUGroup(s): ' . $ugroups . 'Tracker Role(s): ' . $roles . ', Notify closed artifacts: ' . $notify_closed_artifacts],
            );
            return (bool) $reminder;
        } else {
            $errorMessage = sprintf(dgettext('tuleap-tracker', 'Cannot add new date reminder on the field %1$s for the tracker %2$s'), $this->getTracker()->getId(), $fieldId);
            throw new Tracker_DateReminderException($errorMessage);
        }
    }

    /**
     * Throws an excpetion when an enabled date reminder having same params (field, notification Type and the distance) already exist
     *
     * @param int $fieldId Id of the date field
     * @param int $notificationType 0 if before, 1 if after the value of the date field
     * @param int $distance Distance from the value of the date fiels
     */
    private function checkDuplicatedRemindersAtCreation($fieldId, $notificationType, $distance): void
    {
        $dupilcatedReminders = $this->date_reminder_dao->doesARemindersAlreadyExist(
            $this->getTracker()->getId(),
            $fieldId,
            $notificationType,
            $distance
        );

        if ($dupilcatedReminders) {
            $errorMessage = $GLOBALS['Language']->getText('project_admin_utils', 'tracker_date_reminder_duplicated');
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
     */
    private function checkDuplicatedRemindersAEdition($fieldId, $notificationType, $distance, int $reminderId): void
    {
        $dupilcatedReminders = $this->date_reminder_dao->doesAnotherRemindersAlreadyExist(
            $this->getTracker()->getId(),
            $fieldId,
            $notificationType,
            $distance,
            $reminderId,
        );

        if ($dupilcatedReminders) {
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
        $fieldType = $tff->getType($trackerDateField);
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
            $notificationType        = $this->date_reminder_renderer->validateNotificationType($request);
            $distance                = $this->date_reminder_renderer->validateDistance($request);
            $status                  = $this->date_reminder_renderer->validateStatus($request);
            $notify_closed_artifacts = $this->date_reminder_renderer->validateNotifyClosedArtifacts($request);
            $notified                = $this->date_reminder_renderer->scindReminderNotifiedPeople($request);
            $ugroups                 = $this->date_reminder_renderer->validateReminderUgroups($notified[0]);
            $roles                   = $this->date_reminder_renderer->validateReminderRoles($notified[1]);
            if (! empty($ugroups)) {
                $ugroups = join(',', $ugroups);
            } else {
                $ugroups = '';
            }
            $fieldId = $this->date_reminder_renderer->validateFieldId($request);
            if ($status == 1) {
                $this->checkDuplicatedRemindersAEdition($fieldId, $notificationType, $distance, (int) $reminder->getId());
                $this->isReminderBeforeOpenDate($fieldId, $notificationType);
            }
        } catch (Tracker_DateReminderException $e) {
            $GLOBALS['Response']->addFeedback('error', $e->getMessage());
            $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/notifications/' . urlencode((string) $this->getTracker()->getId()) . '/');
            exit();
        }
        $updateReminder = $this->date_reminder_dao->updateDateReminder(
            (int) $reminder->getId(),
            $ugroups,
            $roles,
            $notificationType,
            $distance,
            $status,
            (bool) $notify_closed_artifacts,
        );
        if ($updateReminder) {
            $roles      = implode(',', $roles);
            $historyDao = new ProjectHistoryDao();
            $historyDao->groupAddHistory(
                'tracker_date_reminder_edit',
                $this->getTracker()->getName() . ':' . $reminder->getId(),
                $this->getTracker()->getGroupId(),
                ['Id: ' . $reminder->getId() . ', Type: ' . $notificationType . ', ProjectUGroup(s): ' . $ugroups . ', Tracker Role(s): ' . $roles . ', Day(s): ' . $distance . ', Status: ' . $status . ', Notify closed artifacts: ' . $notify_closed_artifacts],
            );
            return $updateReminder;
        } else {
            $errorMessage = sprintf(dgettext('tuleap-tracker', 'Cannot update the date reminder %1$s'), $reminder->getId());
            throw new Tracker_DateReminderException($errorMessage);
        }
    }

    /**
     * Build a reminder instance
     */
    public function getInstanceFromRow(array $row): Tracker_DateReminder
    {
        $roles         = [];
        $roles_from_db = $this->date_reminder_dao->getRolesByReminderId((int) $row['reminder_id']);
        foreach ($roles_from_db as $role_from_db) {
            switch ($role_from_db) {
                case 1:
                    $roles[] = new Tracker_DateReminder_Role_Submitter();
                    break;

                case 2:
                    $roles[] = new Tracker_DateReminder_Role_Assignee();
                    break;

                case 3:
                    $roles[] = new Tracker_DateReminder_Role_Commenter();
                    break;

                default:
                    break;
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
            $row['status'],
            (bool) $row['notify_closed_artifacts'],
        );
    }

    /**
     * Get the reminder
     *
     * @param int $reminderId The reminder id
     */
    public function getReminder($reminderId): ?Tracker_DateReminder
    {
        $row = $this->date_reminder_dao->searchById($reminderId);
        if ($row !== null) {
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

    public function deleteTrackerReminder(Tracker_DateReminder $reminder): void
    {
        $this->date_reminder_dao->deleteReminder((int) $reminder->getId());
        $historyDao = new ProjectHistoryDao();
        $historyDao->groupAddHistory(
            'tracker_date_reminder_delete',
            $this->tracker->getName(),
            $this->tracker->getGroupId(),
            ['Id: ' . $reminder->getId()]
        );
    }
}
