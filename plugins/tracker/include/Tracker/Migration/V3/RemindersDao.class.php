<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
 *
 * This file is a part of Tuleap.
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

class Tracker_Migration_V3_RemindersDao extends DataAccessObject
{

    public function create($tv3_id, $tv5_id)
    {
        $tv3_id = $this->da->escapeInt($tv3_id);
        $tv5_id = $this->da->escapeInt($tv5_id);

        if (! $this->isPluginInstalled()) {
            return;
        }

        $sql = "SELECT tracker_field.id as field_id, notified_people, notification_type, notification_start, recurse, frequency
                FROM artifact_date_reminder_settings
                    INNER JOIN tracker_field ON (old_id = field_id AND tracker_id = $tv5_id AND formElement_type = 'date')
                WHERE group_artifact_id = $tv3_id";
        foreach ($this->retrieve($sql) as $old_date_reminder) {
            $ugroups = $this->extractUgroups($old_date_reminder['notified_people']);
            $roles = $this->extractTrackerRoles($old_date_reminder['notified_people']);
            if (! $ugroups && !$roles) {
                continue;
            }

            $notification_type = $old_date_reminder['notification_type'];
            $nb_emails         = $old_date_reminder['recurse'];
            $frequency         = $old_date_reminder['frequency'];
            $field_id          = $old_date_reminder['field_id'];
            $start             = $this->getStart($old_date_reminder);

            $this->createReminderList($nb_emails, $tv5_id, $field_id, $ugroups, $roles, $notification_type, $start, $frequency);
        }
    }

    private function isPluginInstalled()
    {
        $sql_v3 = "SHOW TABLES LIKE 'artifact_date_reminder_settings'";

        return count($this->retrieve($sql_v3)) > 0;
    }

    private function getStart($old_date_reminder)
    {
        $start = $old_date_reminder['notification_start'];
        if ($old_date_reminder['notification_type'] == Tracker_DateReminder::AFTER) {
            $start = -$start;
        }
        return $start;
    }

    private function createReminderList($nb_emails, $tv5_id, $field_id, $ugroups, $roles, $notification_type, $start, $frequency)
    {
        for ($i = 0; $i < $nb_emails; $i++) {
            $this->createReminder($i, $tv5_id, $field_id, $ugroups, $roles, $notification_type, $start, $frequency);
        }
    }

    private function createReminder($i, $tv5_id, $field_id, $ugroups, $roles, $notification_type, $start, $frequency)
    {
        $status  = Tracker_DateReminder::ENABLED;
        $ugroups = $this->da->quoteSmart($ugroups);

        $distance = $start - $i * $frequency;
        if ($distance < 0) {
            $distance = abs($distance);
            $notification_type = Tracker_DateReminder::AFTER;
        }
        $sql = "INSERT INTO tracker_reminder (tracker_id, field_id, ugroups, notification_type, distance, status)
                VALUES ($tv5_id, $field_id, $ugroups,  $notification_type, $distance, $status)";
        $reminderId = $this->updateAndGetLastId($sql);
        if ($reminderId && !empty($roles)) {
            $values = array();
            foreach ($roles as $role) {
                $role = (int) $this->da->escapeInt($role);
                $values[] = " (
                        " . $reminderId . ",
                        " . $role . "
                    )";
            }
            $values = implode(', ', $values);
            $sql = "INSERT INTO tracker_reminder_notified_roles
                        (
                        reminder_id,
                        role_id
                        )
                    VALUES " . $values;
            $this->update($sql);
        }
    }

    /**
     * Transform the following list '2,g15,4,g103' into '15,103'
     *
     * @param string $notified_people
     *
     * @return string
     */
    private function extractUgroups($notified_people)
    {
        $ugroups = array();
        foreach (explode(',', $notified_people) as $id) {
            $id = trim($id);
            if ($id[0] == 'g') {
                $ugroups[] = substr($id, 1);
            }
        }
        return implode(',', $ugroups);
    }

    /**
     * Transform the following list '2,g15,4,g103' into '2,3'
     * No CC role for TV5
     * The Commentator role has id = 3 for TV5 instead of 4 for TV3
     *
     * @param string $notified_people
     *
     * @return Array
     */
    private function extractTrackerRoles($notified_people)
    {
        $roles = array();
        foreach (explode(',', $notified_people) as $id) {
            $id = trim($id);
            $role = array("1", "2", "4");
            if (in_array($id, $role)) {
                $roles[] = ($id == "4") ? "3" : $id;
            }
        }
        return $roles;
    }
}
