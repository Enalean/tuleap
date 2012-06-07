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

class Tracker_DateReminderDao extends DataAccessObject {

    /**
     * Constructor of the class
     *
     * @return Void
     */
    function __construct() {
        parent::__construct();
        $this->tableName = 'tracker_reminder';
    }

    /**
     * Get date reminders
     *
     * @param Integer trackerId Id of the tracker (optional)
     *
     * @return DataAccessResult
     */
    public function getDateReminders($trackerId = null) {
        $condition = "";
        if ($trackerId) {
            $trackerId = $this->da->escapeInt($trackerId);
            $condition = "AND tracker_id = ".$trackerId;
        }
        $sql = "SELECT *
                FROM ".$this->tableName."
                WHERE status = 1
                  ".$condition;
        return $this->retrieve($sql);
    }

    /**
     * Add a date reminder
     *
     * @param Integer $trackerId        Id of the tracker
     * @param Integer $fieldId          Id of the date field
     * @param Integer $ugroupId         Id of the user group
     * @param Integer $notificationType 0 if before, 1 if after the value of the date field
     * @param Integer $distance         Distance from the value of the date fiels
     * @param Integer $status           0 if disabled, 1 if enabled
     *
     * @return Boolean
     */
    function addDateReminder($trackerId, $fieldId, $ugroupId, $notificationType = 0, $distance = 0, $status = 1) {
        $trackerId        = $this->da->escapeInt($trackerId);
        $fieldId          = $this->da->escapeInt($fieldId);
        $ugroupId         = $this->da->escapeInt($ugroupId);
        $notificationType = $this->da->escapeInt($notificationType);
        $distance         = $this->da->escapeInt($distance);
        $status           = $this->da->escapeInt($status);
        $sql = "INSERT INTO ".$this->tableName."
                (
                tracker_id,
                field_id,
                ugroup_id,
                notification_type,
                distance,
                status
                )
                VALUES
                (
                ".$trackerId.",
                ".$fieldId.",
                ".$ugroupId.",
                ".$notificationType.",
                ".$distance.",
                ".$status.",
                )";
        return $this->update($sql);
    }

    /**
     * Update a date reminder
     *
     * @param Integer $reminderId       Id of the reminder
     * @param Integer $fieldId          Id of the date field
     * @param Integer $ugroupId         Id of the user group
     * @param Integer $notificationType 0 if before, 1 if after the value of the date field
     * @param Integer $distance         Distance from the value of the date fiels
     * @param Integer $status           0 if disabled, 1 if enabled
     *
     * @return Boolean
     */
    function updateDateReminder($reminderId, $fieldId, $ugroupId, $notificationType = 0, $distance = 0, $status = 1) {
        $reminderId       = $this->da->escapeInt($reminderId);
        $fieldId          = $this->da->escapeInt($fieldId);
        $ugroupId         = $this->da->escapeInt($ugroupId);
        $notificationType = $this->da->escapeInt($notificationType);
        $distance         = $this->da->escapeInt($distance);
        $status           = $this->da->escapeInt($status);
        $sql = "Update ".$this->tableName."
                SET
                field_id          = ".$fieldId.",
                ugroup_id         = ".$ugroupId.",
                notification_type = ".$notificationType.",
                distance          = ".$distance.",
                status            = ".$status."
                WHERE reminder_id = ".$reminderId;
        return $this->update($sql);
    }

}

?>