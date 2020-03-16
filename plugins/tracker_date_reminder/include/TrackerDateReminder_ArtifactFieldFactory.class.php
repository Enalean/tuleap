<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

class TrackerDateReminder_ArtifactFieldFactory
{
    protected $fieldsWithNotification = array();
    public function __construct()
    {
    }
    /**
     * Return all date fields used
     *
     *                @return array
     */
    public function getUsedDateFields(ArtifactFieldFactory $art_field_fact)
    {
        $result_fields = array();
        foreach ($art_field_fact->USAGE_BY_NAME as $key => $field) {
            if ($field->getUseIt() == 1 && $field->isDateField()) {
                $result_fields[$key] = $field;
            }
        }
        return $result_fields;
    }

    public function cacheFieldsWithNotification($group_artifact_id)
    {
        $sql = 'SELECT field_id' .
               ' FROM artifact_date_reminder_settings' .
               ' WHERE group_artifact_id = ' . db_ei($group_artifact_id);
        $res = db_query($sql);
        if ($res && !db_error($res)) {
            while (($row = db_fetch_array($res))) {
                $this->fieldsWithNotification[$row['field_id']] = true;
            }
        }
    }

    public function notificationEnabled($field_id)
    {
        return isset($this->fieldsWithNotification[$field_id]);
    }
}
