<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\TestManagement\Step\Execution\Field;

class StepExecutionChangesetValueDao extends \Tracker_FormElement_Field_ValueDao
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'plugin_testmanagement_changeset_value_stepexec';
    }

    /**
     * @param int $changeset_value_id
     * @return \DataAccessResult|false
     * @psalm-ignore-falsable-return
     */
    public function searchById($changeset_value_id)
    {
        $changeset_value_id = $this->da->escapeInt($changeset_value_id);

        $sql = "SELECT def.*, exec.status
                FROM plugin_testmanagement_changeset_value_stepexec AS exec
                INNER JOIN plugin_testmanagement_changeset_value_stepdef AS def
                  ON (exec.stepdef_id = def.id)
                WHERE exec.changeset_value_id = $changeset_value_id
                ORDER BY def.rank ASC";

        return $this->retrieve($sql);
    }

    public function create(int $changeset_value_id, array $steps): bool
    {
        $changeset_value_id = $this->da->escapeInt($changeset_value_id);
        $values             = [];
        foreach ($steps as $id => $status) {
            $id       = $this->da->escapeInt($id);
            $status   = $this->da->quoteSmart($status);
            $values[] = "($changeset_value_id, $id, $status)";
        }
        if ($values) {
            $values = implode(',', $values);
            $sql    = "INSERT INTO plugin_testmanagement_changeset_value_stepexec(changeset_value_id, stepdef_id, status)
                    VALUES $values";

            return $this->update($sql);
        }

        return false;
    }

    public function createNoneValue(int $tracker_id, int $field_id): void
    {
        $this->createNoneChangesetValue($tracker_id, $field_id);
    }

    public function keep(int $from, int $to): bool
    {
        $from = $this->da->escapeInt($from);
        $to   = $this->da->escapeInt($to);
        $sql  = "INSERT INTO plugin_testmanagement_changeset_value_stepexec(changeset_value_id, stepdef_id, status)
                SELECT $to, stepdef_id, status
                FROM plugin_testmanagement_changeset_value_stepexec
                WHERE changeset_value_id = $from";

        return $this->update($sql);
    }
}
