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

namespace Tuleap\TestManagement\Step\Definition\Field;

use Tracker_FormElement_Field_ValueDao;

class StepDefinitionChangesetValueDao extends Tracker_FormElement_Field_ValueDao
{

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'plugin_testmanagement_changeset_value_stepdef';
    }

    public function searchById($changeset_value_id)
    {
        $changeset_value_id = $this->da->escapeInt($changeset_value_id);

        $sql = "SELECT *
                FROM plugin_testmanagement_changeset_value_stepdef
                WHERE changeset_value_id = $changeset_value_id
                ORDER BY rank ASC";

        return $this->retrieve($sql);
    }

    public function create($changeset_value_id, array $steps)
    {
        $changeset_value_id = $this->da->escapeInt($changeset_value_id);
        $values             = [];
        $rank               = StepDefinition::START_RANK;
        foreach ($steps['description'] as $description) {
            $description = trim($description);
            if (! $description) {
                continue;
            }
            $description = $this->da->quoteSmart($description);

            $values[] = "($changeset_value_id, $description, $rank)";
            $rank++;
        }
        if ($values) {
            $values = implode(',', $values);
            $sql    = "INSERT INTO plugin_testmanagement_changeset_value_stepdef(changeset_value_id, description, rank)
                    VALUES $values";

            return $this->update($sql);
        }

        return true;
    }

    public function createNoneValue($tracker_id, $field_id)
    {
        $this->createNoneChangesetValue($tracker_id, $field_id);
    }

    public function keep($from, $to)
    {
        $from = $this->da->escapeInt($from);
        $to   = $this->da->escapeInt($to);
        $sql  = "INSERT INTO plugin_testmanagement_changeset_value_stepdef(changeset_value_id, description, rank)
                SELECT $to, description, rank
                FROM plugin_testmanagement_changeset_value_stepdef
                WHERE changeset_value_id = $from";

        return $this->update($sql);
    }
}
