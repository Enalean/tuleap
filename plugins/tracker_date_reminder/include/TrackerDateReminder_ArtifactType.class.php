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

class TrackerDateReminder_ArtifactType
{
    protected $at;

    public function __construct(ArtifactType $at)
    {
        $this->at = $at;
    }


    /**
    * Add artifact to artifact_date_reminder_processing table
    *
    *  @param field_id: the field id
    *  @param artifact_id: the artifact id
    *  @param group_artifact_id: the tracker id
    *
    * @return nothing
    */
    public function addArtifactToDateReminderProcessing($field_id, $artifact_id, $group_artifact_id)
    {
        $art_field_fact = new ArtifactFieldFactory($this->at);

        if ($field_id <> 0) {
            $sql = sprintf(
                'SELECT reminder_id, field_id FROM artifact_date_reminder_settings'
                            . ' WHERE group_artifact_id=%d'
                            . ' AND field_id=%d',
                db_ei($group_artifact_id),
                db_ei($field_id)
            );
        } else {
            $sql = sprintf(
                'SELECT reminder_id, field_id FROM artifact_date_reminder_settings'
                            . ' WHERE group_artifact_id=%d',
                db_ei($group_artifact_id)
            );
        }
        $res = db_query($sql);
        if (db_numrows($res) > 0) {
            while ($rows = db_fetch_array($res)) {
                $reminder_id = $rows['reminder_id'];
                $fid = $rows['field_id'];
                $field = $art_field_fact->getFieldFromId($fid);

                $sql1 = sprintf(
                    'SELECT valueDate FROM artifact_field_value'
                               . ' WHERE artifact_id=%d'
                               . ' AND field_id=%d',
                    db_ei($artifact_id),
                    db_ei($fid)
                );
                $res1 = db_query($sql1);

                if (! $field->isStandardField()) {
                    if (db_numrows($res1) > 0) {
                        $valueDate = db_result($res1, 0, 'valueDate');
                        if ($valueDate <> 0 && $valueDate <> null) {
                            //the date field is not special (value is stored in 'artifact_field_value' table)
                            $ins = sprintf(
                                'INSERT INTO artifact_date_reminder_processing'
                                            . ' (reminder_id,artifact_id,field_id,group_artifact_id,notification_sent)'
                                            . ' VALUES(%d,%d,%d,%d,%d)',
                                db_ei($reminder_id),
                                db_ei($artifact_id),
                                db_ei($fid),
                                db_ei($group_artifact_id),
                                0
                            );
                            $result = db_query($ins);
                        }
                    }
                } else {
                    //End Date
                    $sql2 = sprintf(
                        'SELECT close_date FROM artifact'
                                    . ' WHERE artifact_id=%d'
                                    . ' AND group_artifact_id=%d',
                        db_ei($artifact_id),
                        db_ei($group_artifact_id)
                    );
                    $res2 = db_query($sql2);
                    if (db_numrows($res2) > 0) {
                        $close_date = db_result($res2, 0, 'close_date');
                        if ($close_date <> 0 && $close_date <> null) {
                            $ins = sprintf(
                                'INSERT INTO artifact_date_reminder_processing'
                                            . ' (reminder_id,artifact_id,field_id,group_artifact_id,notification_sent)'
                                            . ' VALUES(%d,%d,%d,%d,%d)',
                                db_ei($reminder_id),
                                db_ei($artifact_id),
                                db_ei($fid),
                                db_ei($group_artifact_id),
                                0
                            );
                            $result = db_query($ins);
                        }
                    }
                }
            }
        }
    }

    /**
    * Delete artifact from artifact_date_reminder_processing table
    *
    *  @param field_id: the field id
    *  @param artifact_id: the artifact id
    *  @param group_artifact_id: the tracker id
    *
    * @return nothing
    */
    public function deleteArtifactFromDateReminderProcessing($field_id, $artifact_id, $group_artifact_id)
    {
        if ($field_id == 0) {
            $del = sprintf(
                'DELETE FROM artifact_date_reminder_processing'
                            . ' WHERE artifact_id=%d'
                            . ' AND group_artifact_id=%d',
                db_ei($artifact_id),
                db_ei($group_artifact_id)
            );
        } else {
            $del = sprintf(
                'DELETE FROM artifact_date_reminder_processing'
                            . ' WHERE artifact_id=%d'
                            . ' AND field_id=%d'
                            . ' AND group_artifact_id=%d',
                db_ei($artifact_id),
                db_ei($field_id),
                db_ei($group_artifact_id)
            );
        }
        $result = db_query($del);
    }
}
