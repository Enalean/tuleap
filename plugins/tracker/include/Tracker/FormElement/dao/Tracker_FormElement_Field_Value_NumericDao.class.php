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


abstract class Tracker_FormElement_Field_Value_NumericDao extends Tracker_FormElement_Field_ValueDao {

    /**
     * Retrieves the value of the given field at the most recent time BEFORE the given timestamp
     *
     * @param Integer $artifact_id
     * @param Integer $field_id
     * @param Integer $timestamp
     *
     * @return Array
     */
    public function getValueAt($artifact_id, $field_id, $timestamp) {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $field_id    = $this->da->escapeInt($field_id);
        $timestamp   = $this->da->escapeInt($timestamp);

        $sql = "SELECT cvi.value
                FROM        tracker_changeset       c1
                  LEFT JOIN tracker_changeset       c2  ON (c2.artifact_id         = c1.artifact_id AND c1.submitted_on < c2.submitted_on AND c2.submitted_on < $timestamp)
                  JOIN      tracker_changeset_value cv  ON (cv.changeset_id        = c1.id          AND cv.field_id     = $field_id)
                  JOIN      $this->table_name       cvi ON (cvi.changeset_value_id = cv.id)
                WHERE c1.artifact_id  = $artifact_id
                  AND c1.submitted_on < $timestamp
                  AND c2.id          IS NULL";
        return $this->retrieveFirstRow($sql);
    }
}

?>
