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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\Semantic\Status;

use ParagonIE\EasyDB\EasyStatement;
use Tracker;
use Tuleap\CrossTracker\Report\Query\ParametrizedFromWhere;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;

final class EqualComparisonFromWhereBuilder implements FromWhereBuilder
{
    public function getFromWhere(Metadata $metadata, Comparison $comparison, array $trackers)
    {
        $tracker_ids           = array_map(
            function (Tracker $tracker) {
                return $tracker->getId();
            },
            $trackers
        );
        $tracker_ids_statement = EasyStatement::open()->in('field.tracker_id IN (?*)', $tracker_ids);

        $from = "LEFT JOIN (
            tracker_changeset_value AS changeset_value_status
            INNER JOIN (
                SELECT DISTINCT field_id
                FROM tracker_semantic_status AS field
                WHERE $tracker_ids_statement
            ) AS equal_status_field ON (equal_status_field.field_id = changeset_value_status.field_id)
            INNER JOIN tracker_changeset_value_list AS tracker_changeset_value_status
                ON (
                    tracker_changeset_value_status.changeset_value_id = changeset_value_status.id
                )
        ) ON (
            changeset_value_status.changeset_id = tracker_artifact.last_changeset_id
        )";

        $where = "changeset_value_status.changeset_id IS NOT NULL
            AND tracker_changeset_value_status.bindvalue_id IN (
                SELECT open_value_id
                FROM tracker_semantic_status AS field
                WHERE $tracker_ids_statement
            )";

        return new ParametrizedFromWhere($from, $where, $tracker_ids, $tracker_ids);
    }
}
