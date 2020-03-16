<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

class Tracker_FormElement_Field_Value_DateDao extends Tracker_FormElement_Field_ValueDao
{

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_changeset_value_date';
    }

    public function create($changeset_value_id, $value)
    {
        $changeset_value_id = $this->da->escapeInt($changeset_value_id);
        if ($value === false) {
            $value = "NULL";
        } else {
            $value = $this->da->escapeInt($value);
        }
        $sql = "INSERT INTO $this->table_name(changeset_value_id, value)
                VALUES ($changeset_value_id, $value)";
        return $this->update($sql);
    }

    /**
     * create none value
     * @param int $tracker_id
     * @param int $field_id
     * @return
     */
    public function createNoneValue($tracker_id, $field_id)
    {
        $changeset_value_ids = $this->createNoneChangesetValue($tracker_id, $field_id);
        if ($changeset_value_ids === false) {
            return false;
        }
        $sql = " INSERT INTO $this->table_name(changeset_value_id, value)
                 VALUES
                  ( " . implode(' , NULL ),' . "\n" . ' ( ', $changeset_value_ids) . ", NULL)";
        return $this->update($sql);
    }

    public function keep($from, $to)
    {
        $from = $this->da->escapeInt($from);
        $to   = $this->da->escapeInt($to);
        $sql = "INSERT INTO $this->table_name(changeset_value_id, value)
                SELECT $to, value
                FROM $this->table_name
                WHERE changeset_value_id = $from";
        return $this->update($sql);
    }

    /**
     * Retrieve the list of artifact id corresponding to a date field having a specific value
     *
     * @param int $fieldId Date field
     * @param int $date Value of the date field
     *
     * @return
     */
    public function getArtifactsByFieldAndValue($fieldId, $date)
    {
        $fieldId  = $this->da->escapeInt($fieldId);
        $date     = $this->da->escapeInt($date);
        $halfDay  = 60 * 60 * 12;
        $minDate  = $date - $halfDay;
        $maxDate  = $date + $halfDay;
        $sql      = "SELECT t.id AS artifact_id FROM
                     tracker_changeset_value_date d
                     JOIN tracker_changeset_value v on v.id = d.changeset_value_id
                     JOIN tracker_artifact t on t.last_changeset_id = v.changeset_id
                     WHERE d.value BETWEEN " . $minDate . " AND " . $maxDate . "
                       AND v.field_id = " . $fieldId;
        return $this->retrieve($sql);
    }
}
