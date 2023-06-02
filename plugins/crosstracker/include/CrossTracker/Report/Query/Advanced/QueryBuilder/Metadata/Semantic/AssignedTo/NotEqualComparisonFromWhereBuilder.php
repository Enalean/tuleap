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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\Semantic\AssignedTo;

use ParagonIE\EasyDB\EasyStatement;
use Tracker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\ListValueExtractor;
use Tuleap\CrossTracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;
use Tuleap\CrossTracker\Report\Query\ParametrizedFromWhere;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;

final class NotEqualComparisonFromWhereBuilder implements FromWhereBuilder
{
    public function __construct(private readonly ListValueExtractor $extractor, private readonly \UserManager $user_manager)
    {
    }

    /**
     * @param Tracker[] $trackers
     */
    public function getFromWhere(Metadata $metadata, Comparison $comparison, array $trackers): IProvideParametrizedFromAndWhereSQLFragments
    {
        $values = $this->extractor->extractCollectionOfValues($comparison);
        $value  = $values[0];

        $from = 'INNER JOIN tracker_semantic_contributor AS assigned_to_field_not_equal
            ON (
              assigned_to_field_not_equal.tracker_id = tracker_artifact.tracker_id
            )
            LEFT JOIN (
                tracker_changeset_value AS changeset_value_assigned_to_not_equal
                INNER JOIN tracker_changeset_value_list AS changeset_value_list_assigned_to_not_equal
                ON (
                    changeset_value_list_assigned_to_not_equal.changeset_value_id = changeset_value_assigned_to_not_equal.id
                )
            ) ON (
                changeset_value_assigned_to_not_equal.changeset_id = tracker_artifact.last_changeset_id
                AND changeset_value_assigned_to_not_equal.field_id = assigned_to_field_not_equal.field_id
            )';

        $from_parameters = [];

        if ($value !== '') {
            return $this->getFromWhereForNonEmptyCondition(
                $from,
                $from_parameters,
                $trackers,
                (string) $value
            );
        }

        return $this->getFromWhereForEmptyCondition($from, $from_parameters);
    }

    private function getFromWhereForEmptyCondition(string $from, array $from_parameters): ParametrizedFromWhere
    {
        $where = 'changeset_value_assigned_to_not_equal.changeset_id IS NOT NULL
            AND changeset_value_list_assigned_to_not_equal.bindvalue_id != ?';

        $where_parameters = [\Tracker_FormElement_Field_List::NONE_VALUE];

        return new ParametrizedFromWhere(
            $from,
            $where,
            $from_parameters,
            $where_parameters
        );
    }

    /**
     * @param Tracker[] $trackers
     */
    private function getFromWhereForNonEmptyCondition(
        string $from,
        array $from_parameters,
        array $trackers,
        string $value,
    ): ParametrizedFromWhere {
        $tracker_ids           = array_map(
            function (Tracker $tracker) {
                return $tracker->getId();
            },
            $trackers
        );
        $tracker_ids_condition = EasyStatement::open()->in('artifact.tracker_id IN (?*)', $tracker_ids);

        $user = $this->user_manager->getUserByUserName($value);

        $where = "changeset_value_assigned_to_not_equal.changeset_id IS NOT NULL
            AND tracker_artifact.id NOT IN (
                SELECT artifact.id
                FROM tracker_artifact AS artifact
                INNER JOIN tracker_changeset AS c ON (artifact.last_changeset_id = c.id)
                INNER JOIN tracker_semantic_contributor AS not_equal_assigned_to_field
                ON (
                    not_equal_assigned_to_field.tracker_id = artifact.tracker_id
                )
                LEFT JOIN (
                    tracker_changeset_value AS changeset_value_assigned_to
                    INNER JOIN tracker_changeset_value_list AS tracker_changeset_value_assigned_to
                    ON (
                        tracker_changeset_value_assigned_to.changeset_value_id = changeset_value_assigned_to.id
                    )
                ) ON (
                    changeset_value_assigned_to.changeset_id = artifact.last_changeset_id
                    AND changeset_value_assigned_to.field_id = not_equal_assigned_to_field.field_id
                )
                WHERE $tracker_ids_condition
                    AND changeset_value_assigned_to.changeset_id IS NOT NULL
                    AND tracker_changeset_value_assigned_to.bindvalue_id = ?
            )";

        $where_parameters = array_merge(
            $tracker_ids_condition->values(),
            [$user->getId()]
        );

        return new ParametrizedFromWhere(
            $from,
            $where,
            $from_parameters,
            $where_parameters
        );
    }
}
