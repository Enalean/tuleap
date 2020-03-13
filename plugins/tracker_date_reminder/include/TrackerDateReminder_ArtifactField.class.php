<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

class TrackerDateReminder_ArtifactField
{
    public function getDateFieldReminderSettings($field_id, $group_artifact_id)
    {
        $sql = sprintf(
            'SELECT * FROM artifact_date_reminder_settings'
                      . ' WHERE group_artifact_id=%d'
                      . ' AND field_id=%d',
            $group_artifact_id,
            $field_id
        );
        $result = db_query($sql);
        return $result;
    }

    /**
     * Delete reminder settings of a specific field
     *
     * @param field_id: the field id
     * @param group_artifact_id: the tracker id
     *
     * @return result set
     */
    public function deleteFieldReminderSettings($field_id, $group_artifact_id)
    {
        $del = sprintf(
            'DELETE FROM artifact_date_reminder_settings'
                . ' WHERE field_id=%d'
                . ' AND group_artifact_id=%d',
            $field_id,
            $group_artifact_id
        );
        $result = db_query($del);
        $rem = sprintf(
            'DELETE FROM artifact_date_reminder_processing'
                . ' WHERE field_id=%d'
                . ' AND group_artifact_id=%d',
            $field_id,
            $group_artifact_id
        );
        $result = db_query($rem);
    }


    public function populateProcessingForField(ArtifactType $at, $field_id, $group_artifact_id)
    {
        //Now populate the 'artifact_date_reminder_processing' table with concerned artifacts
        $art = sprintf(
            'SELECT * FROM artifact'
            . ' WHERE group_artifact_id=%d'
            . ' AND status_id <> 3',
            db_ei($group_artifact_id)
        );
        $res_art = db_query($art);
        if (db_numrows($res_art) > 0) {
            $tdrArtifactType = new TrackerDateReminder_ArtifactType($at);
            while ($arr = db_fetch_array($res_art)) {
                $tdrArtifactType->addArtifactToDateReminderProcessing($field_id, $arr['artifact_id'], $group_artifact_id);
            }
        }
    }

    /**
     *  updateDateFieldReminderSettings - use this to update the date-fields reminder settings in the database.
     *
     *  @param  $field_id   The date field concerned by the notification.
     *  @param  $group_artifact_id  The tracker id
     *  @param  $start  When will the notification start taking effect, with regards to date occurence (in days)
     *  @param  $type   What is the type of the notification (after date occurence, before date occurence)
     *  @param  $frequency  At which frequency (in days) the notification wil occur
     *  @param  $recurse    How many times the notification mail will be sent
     *  @param  $submitter  Is submitter notified ?
     *  @param  $assignee   Is assignee notified ?
     *  @param  $cc Is cc notified ?
     *  @param  $commenter  Is commetner notified ?
     *
     *  @return true on success, false on failure.
     */
    public function updateDateFieldReminderSettings(ArtifactType $at, ArtifactField $field, $group_artifact_id, $start, $notif_type, $frequency, $recurse, $people_notified)
    {
        $res = $this->getDateFieldReminderSettings($field->getID(), $group_artifact_id);
        if ($res && !db_error($res)) {
            $notified_users = implode(",", $people_notified);
            if (db_numrows($res) == 0) {
                // No reminder, create it
                $insert = 'INSERT INTO artifact_date_reminder_settings' .
                          '(field_id, group_artifact_id, notification_start, notification_type, frequency, recurse, notified_people)' .
                          ' VALUES' .
                          ' (' . db_ei($field->getId()) . ',' . db_ei($group_artifact_id) . ',' . db_ei($start) . ',' . db_ei($notif_type) . ',' . db_ei($frequency) . ',' . db_ei($recurse) . ',"' . db_es($notified_users) . '")';
                $inserted = db_query($insert);
                if ($inserted) {
                    $this->populateProcessingForField($at, $field->getId(), $group_artifact_id);
                    return true;
                }
                return false;
            } else {
                //update reminder settings
                $update = sprintf(
                    'UPDATE artifact_date_reminder_settings'
                    . ' SET notification_start=%d'
                    . ' , notification_type=%d'
                    . ' , frequency=%d'
                    . ' , recurse=%d'
                    . ' , notified_people="%s"'
                    . ' WHERE group_artifact_id=%d'
                    . ' AND field_id=%d',
                    db_ei($start),
                    db_ei($notif_type),
                    db_ei($frequency),
                    db_ei($recurse),
                    db_es($notified_users),
                    db_ei($group_artifact_id),
                    db_ei($field->getId())
                );
                $result = db_query($update);
                return $result;
            }
        } else {
            return false;
        }
    }
}
