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

use Tracker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\ListValueExtractor;
use Tuleap\CrossTracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;
use Tuleap\CrossTracker\Report\Query\ParametrizedFromWhere;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;

final class EqualComparisonFromWhereBuilder implements FromWhereBuilder
{
    public function __construct(
        private readonly ListValueExtractor $extractor,
        private readonly \UserManager $user_manager,
    ) {
    }

    /**
     * @param Tracker[] $trackers
     */
    public function getFromWhere(Metadata $metadata, Comparison $comparison, array $trackers): IProvideParametrizedFromAndWhereSQLFragments
    {
        $values = $this->extractor->extractCollectionOfValues($comparison);
        $value  = $values[0];

        if ($value !== '') {
            return $this->getFromWhereForNonEmptyCondition((string) $value);
        }

        return $this->getFromWhereForEmptyCondition();
    }

    private function getFromWhereForEmptyCondition(): ParametrizedFromWhere
    {
        $from            = 'INNER JOIN tracker_semantic_contributor AS empty_assigned_to_field
            ON (
                empty_assigned_to_field.tracker_id = tracker_artifact.tracker_id
            )
            LEFT JOIN (
                tracker_changeset_value AS changeset_value_assigned_to
                INNER JOIN tracker_changeset_value_list AS tracker_changeset_value_assigned_to
                ON (
                    tracker_changeset_value_assigned_to.changeset_value_id = changeset_value_assigned_to.id
                )
            ) ON (
                changeset_value_assigned_to.changeset_id = tracker_artifact.last_changeset_id
                AND changeset_value_assigned_to.field_id = empty_assigned_to_field.field_id
            )';
        $from_parameters = [];

        $where            = '(changeset_value_assigned_to.changeset_id IS NULL
            OR tracker_changeset_value_assigned_to.bindvalue_id = ? )';
        $where_parameters = [\Tracker_FormElement_Field_List::NONE_VALUE];

        return new ParametrizedFromWhere(
            $from,
            $where,
            $from_parameters,
            $where_parameters
        );
    }

    private function getFromWhereForNonEmptyCondition(string $value): ParametrizedFromWhere
    {
        $from            = 'INNER JOIN tracker_semantic_contributor AS equal_assigned_to_field
            ON (
                equal_assigned_to_field.tracker_id = tracker_artifact.tracker_id
            )';
        $from_parameters = [];

        $user = $this->user_manager->getUserByUserName($value);

        $where            = 'tracker_artifact.last_changeset_id IN (
            SELECT changeset_value_assigned_to.changeset_id
            FROM tracker_changeset_value AS changeset_value_assigned_to
            INNER JOIN tracker_changeset_value_list AS tracker_changeset_value_assigned_to
            ON (
                tracker_changeset_value_assigned_to.changeset_value_id = changeset_value_assigned_to.id
            )
            WHERE tracker_changeset_value_assigned_to.bindvalue_id = ?
              AND changeset_value_assigned_to.field_id = equal_assigned_to_field.field_id
        )';
        $where_parameters = [$user->getId()];

        return new ParametrizedFromWhere(
            $from,
            $where,
            $from_parameters,
            $where_parameters
        );
    }
}
