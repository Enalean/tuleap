<?php
/**
 * Copyright (c) Enalean, 2012 - 2016. All Rights Reserved.
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

/**
 * Class for field condition DAOs.
 */
class Workflow_Transition_Condition_FieldNotEmpty_Dao extends DataAccessObject
{

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_workflow_transition_condition_field_notempty';
    }

    /**
     * Create a new entry
     *
     * @param int $transition_id The transition the post action belongs to
     *
     */
    public function create($transition_id, $list_field_id)
    {
        $transition_id = $this->da->escapeInt($transition_id);

        $fields = array();
        foreach ($list_field_id as $field_id) {
            if ($field_id != 0) {
                $field_id = $this->da->escapeInt($field_id);
                $fields[] .= " ($transition_id, $field_id)";
            }
        }

        if (count($fields) > 0) {
            $sql = "INSERT INTO $this->table_name (`transition_id`, `field_id`) VALUES ";
            $sql .= implode(",", $fields);
            return $this->update($sql);
        }

        return true;
    }

    /**
     * Search all fieldnotempty conditions belonging to a transition
     *
     * @param int $transition_id The id of the transition
     *
     * @return DataAccessResult
     */
    public function searchByTransitionId($transition_id)
    {
        $transition_id = $this->da->escapeInt($transition_id);

        $sql = "SELECT *
                FROM $this->table_name
                WHERE transition_id = $transition_id
                ORDER BY id";

        return $this->retrieve($sql);
    }

    public function deleteByTransitionId($transition_id)
    {
        $transition_id = $this->da->escapeInt($transition_id);
        $sql = "DELETE
                FROM $this->table_name
                WHERE transition_id = $transition_id";

        return $this->update($sql);
    }

    /**
     * Duplicate condition
     */
    public function duplicate($from_transition_id, $to_transition_id, $field_mapping)
    {
        $from_transition_id = $this->da->escapeInt($from_transition_id);
        $to_transition_id   = $this->da->escapeInt($to_transition_id);

        $case           = array();
        $from_field_ids = array();
        foreach ($field_mapping as $mapping) {
            $from = $this->da->escapeInt($mapping['from']);
            $to   = $this->da->escapeInt($mapping['to']);

            $case[]           = "WHEN $from THEN $to";
            $from_field_ids[] = $from;
        }
        if (count($case)) {
            $from_field_ids = implode(', ', $from_field_ids);
            $new_field_id   = 'CASE field_id ' . implode(' ', $case) . ' END';
            $sql = "INSERT INTO tracker_workflow_transition_condition_field_notempty (transition_id, field_id)
                    SELECT $to_transition_id, $new_field_id
                    FROM tracker_workflow_transition_condition_field_notempty
                    WHERE transition_id = $from_transition_id
                      AND field_id IN ($from_field_ids)";
            return $this->update($sql);
        }
    }

    public function addPermission($permission_type, $object_id, $ugroup_id)
    {
 // WAT ???
        $sql = sprintf(
            "INSERT INTO permissions (object_id, permission_type, ugroup_id)" .
                     " VALUES ('%s', '%s', '%s')",
            $object_id,
            $permission_type,
            $ugroup_id
        );
        return $this->update($sql);
    }

    /** @return bool */
    public function isFieldUsed($field_id)
    {
        $sql = "SELECT NULL
                FROM tracker_workflow_transition_condition_field_notempty
                WHERE field_id = $field_id
                LIMIT 1";
        return count($this->retrieve($sql)) > 0;
    }
}
