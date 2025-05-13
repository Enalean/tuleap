<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\TestManagement\Step\Definition\Field;

use Tracker_Artifact_Changeset_ValueDao;
use Tuleap\DB\DataAccessObject;
use Tuleap\TestManagement\Step\Step;

class StepDefinitionChangesetValueDao extends DataAccessObject
{
    /**
     * @return list<Step>
     */
    public function searchById(int $changeset_value_id): array
    {
        $sql = <<<SQL
            SELECT id, description, description_format, expected_results, expected_results_format, `rank`
            FROM plugin_testmanagement_changeset_value_stepdef
            WHERE changeset_value_id = ?
            ORDER BY `rank` ASC
            SQL;

        $retrieved_steps = $this->getDB()->q($sql, $changeset_value_id);
        return array_values(array_map(
            static fn(array $row) => new Step(
                $row['id'],
                $row['description'],
                $row['description_format'],
                $row['expected_results'],
                $row['expected_results_format'],
                $row['rank'],
            ),
            $retrieved_steps,
        ));
    }

    public function delete(int $changeset_value_id): void
    {
        $this->getDB()->delete('plugin_testmanagement_changeset_value_stepdef', [
            'changeset_value_id' => $changeset_value_id,
        ]);
    }

    /**
     * Function that creates a value record for all artifacts last changeset
     *
     * @return list<int>|false
     */
    public function createNoneChangesetValue(int $tracker_id, int $field_id): array|false
    {
        $changeset_value_dao = new Tracker_Artifact_Changeset_ValueDao();
        $changeset_value_ids = $changeset_value_dao->createFromLastChangesetByTrackerId($tracker_id, $field_id);
        if ($changeset_value_ids === []) {
            return false;
        }
        return $changeset_value_ids;
    }

    /**
     * @param Step[] $steps
     */
    public function create(int $changeset_value_id, array $steps): bool
    {
        if ($steps === []) {
            return true;
        }

        $rank = 1;
        $this->getDB()->insertMany(
            'plugin_testmanagement_changeset_value_stepdef',
            array_map(
                static function (Step $step) use (&$rank, $changeset_value_id) {
                    return [
                        'changeset_value_id'      => $changeset_value_id,
                        'description'             => $step->getDescription(),
                        'description_format'      => $step->getDescriptionFormat(),
                        'expected_results'        => $step->getExpectedResults(),
                        'expected_results_format' => $step->getExpectedResultsFormat(),
                        'rank'                    => $rank++,
                    ];
                },
                $steps,
            ),
        );

        return true;
    }

    public function createNoneValue(int $tracker_id, int $field_id): void
    {
        $this->createNoneChangesetValue($tracker_id, $field_id);
    }

    public function keep(int $from, int $to): bool
    {
        $sql = <<<SQL
            INSERT INTO plugin_testmanagement_changeset_value_stepdef(changeset_value_id, description, description_format, expected_results, expected_results_format, `rank`)
                SELECT ?, description, description_format, expected_results, expected_results_format, `rank`
                FROM plugin_testmanagement_changeset_value_stepdef
                WHERE changeset_value_id = ?
            SQL;

        $this->getDB()->run($sql, $to, $from);
        return true;
    }
}
