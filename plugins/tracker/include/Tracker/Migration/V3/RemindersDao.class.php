<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class Tracker_Migration_V3_RemindersDao extends DataAccessObject {

    public function create($tv3_id, $tv5_id) {
        $tv3_id = $this->da->escapeInt($tv3_id);
        $tv5_id = $this->da->escapeInt($tv5_id);

        $sql = "SELECT tracker_field.id as field_id, notified_people, notification_type, notification_start, recurse, frequency
                FROM artifact_date_reminder_settings
                    INNER JOIN tracker_field ON (old_id = field_id AND tracker_id = $tv5_id AND formElement_type = 'date')
                WHERE group_artifact_id = $tv3_id";
        foreach ($this->retrieve($sql) as $old_date_reminder) {
            $notification_type = $old_date_reminder['notification_type'];
            $nb_emails         = $old_date_reminder['recurse'];
            $frequency         = $old_date_reminder['frequency'];
            $field_id          = $old_date_reminder['field_id'];
            $ugroups           = $this->extractUgroups($old_date_reminder['notified_people']);
            $status            = Tracker_DateReminder::ENABLED;

            if (! $ugroups) {
                continue;
            }
            $ugroups = $this->da->quoteSmart($ugroups);

            $start             = $old_date_reminder['notification_start'];
            if ($notification_type == Tracker_DateReminder::AFTER) {
                $start = -$start;
            }

            for ($i = 0 ; $i < $nb_emails ; $i++) {
                $distance = $start - $i * $frequency;
                if ($distance < 0) {
                    $distance = abs($distance);
                    $notification_type = Tracker_DateReminder::AFTER;
                }
                $sql = "INSERT INTO tracker_reminder (tracker_id, field_id, ugroups, notification_type, distance, status)
                        VALUES ($tv5_id, $field_id, $ugroups, $notification_type, $distance, $status)";
                $this->update($sql);
            }
        }
    }

    /**
     * Transform the following list '2,g15,4,g103' into '15,103'
     *
     * @param string $notified_people
     *
     * @return string
     */
    private function extractUgroups($notified_people) {
        $ugroups = array();
        foreach (explode(',', $notified_people) as $id) {
            $id = trim($id);
            if ($id{0} == 'g') {
                $ugroups[] = substr($id, 1);
            }
        }
        return implode(',', $ugroups);
    }
}
?>
