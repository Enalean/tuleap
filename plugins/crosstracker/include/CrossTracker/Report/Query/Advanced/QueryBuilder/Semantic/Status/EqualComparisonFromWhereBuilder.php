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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Semantic\Status;

use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\FromWhere;

class EqualComparisonFromWhereBuilder implements FromWhereBuilder
{
    public function getFromWhere(Metadata $metadata, Comparison $comparison)
    {
        $from = "LEFT JOIN (
            tracker_changeset_value AS changeset_value_status
            INNER JOIN tracker_semantic_status
                ON (
                    tracker_semantic_status.field_id = changeset_value_status.field_id
                )
            INNER JOIN tracker_changeset_value_list AS tracker_changeset_value_status
                ON (
                    tracker_changeset_value_status.changeset_value_id = changeset_value_status.id
                )
        ) ON (
            tracker_semantic_status.tracker_id = tracker_artifact.tracker_id
            AND changeset_value_status.changeset_id = tracker_artifact.last_changeset_id
        )";

        $where = "changeset_value_status.changeset_id IS NOT NULL
            AND tracker_changeset_value_status.bindvalue_id = tracker_semantic_status.open_value_id";

        return new FromWhere($from, $where);
    }
}
