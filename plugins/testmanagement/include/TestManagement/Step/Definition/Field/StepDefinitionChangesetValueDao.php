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
use Tuleap\TestManagement\Step\Step;

class StepDefinitionChangesetValueDao extends Tracker_FormElement_Field_ValueDao
{

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'plugin_testmanagement_changeset_value_stepdef';
    }

    /**
     * @param int $changeset_value_id
     * @return \DataAccessResult|false
     * @psalm-ignore-falsable-return
     */
    public function searchById($changeset_value_id)
    {
        $changeset_value_id = $this->da->escapeInt($changeset_value_id);

        $sql = "SELECT *
                FROM plugin_testmanagement_changeset_value_stepdef
                WHERE changeset_value_id = $changeset_value_id
                ORDER BY rank ASC";

        return $this->retrieve($sql);
    }

    /**
     * @param Step[] $steps
     *
     */
    public function create(int $changeset_value_id, array $steps): bool
    {
        $changeset_value_id = $this->da->escapeInt($changeset_value_id);
        $values             = [];
        $rank               = 1;
        foreach ($steps as $step) {
            $description             = $this->da->quoteSmart($step->getDescription());
            $description_format      = $this->da->quoteSmart($step->getDescriptionFormat());
            $expected_results        = $this->da->quoteSmart($step->getExpectedResults());
            $expected_results_format = $this->da->quoteSmart($step->getExpectedResultsFormat());

            $values[] = "($changeset_value_id, $description, $description_format, $expected_results, $expected_results_format, $rank)";
            $rank++;
        }
        if ($values) {
            $values = implode(',', $values);
            $sql    = "INSERT INTO plugin_testmanagement_changeset_value_stepdef(changeset_value_id, description, description_format, expected_results, expected_results_format, rank)
                    VALUES $values";

            return $this->update($sql);
        }

        return true;
    }

    public function createNoneValue(int $tracker_id, int $field_id): void
    {
        $this->createNoneChangesetValue($tracker_id, $field_id);
    }

    public function keep(int $from, int $to): bool
    {
        $from = $this->da->escapeInt($from);
        $to   = $this->da->escapeInt($to);
        $sql  = "INSERT INTO plugin_testmanagement_changeset_value_stepdef(changeset_value_id, description, description_format, expected_results, expected_results_format, rank)
                SELECT $to, description, description_format, expected_results, expected_results_format, rank
                FROM plugin_testmanagement_changeset_value_stepdef
                WHERE changeset_value_id = $from";

        return $this->update($sql);
    }
}
