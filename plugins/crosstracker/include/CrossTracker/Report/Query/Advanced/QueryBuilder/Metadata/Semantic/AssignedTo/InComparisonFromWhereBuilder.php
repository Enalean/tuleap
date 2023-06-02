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
use UserManager;

final class InComparisonFromWhereBuilder implements FromWhereBuilder
{
    /**
     * @var ListValueExtractor
     */
    private $extractor;
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(ListValueExtractor $list_value_extractor, UserManager $user_manager)
    {
        $this->extractor    = $list_value_extractor;
        $this->user_manager = $user_manager;
    }

    /**
     * @param Tracker[] $trackers
     * @return IProvideParametrizedFromAndWhereSQLFragments
     */
    public function getFromWhere(Metadata $metadata, Comparison $comparison, array $trackers)
    {
        $values           = $this->extractor->extractCollectionOfValues($comparison);
        $values_condition = EasyStatement::open()->in(
            'tracker_changeset_value_assigned_to.bindvalue_id IN (?*)',
            $this->getUserIdsByUserNames($values)
        );

        $from            = "INNER JOIN tracker_semantic_contributor AS equal_assigned_to_field
            ON (
                equal_assigned_to_field.tracker_id = tracker_artifact.tracker_id
            )";
        $from_parameters = [];

        $where            = "tracker_artifact.last_changeset_id IN (
            SELECT changeset_value_assigned_to.changeset_id
            FROM tracker_changeset_value AS changeset_value_assigned_to
            INNER JOIN tracker_changeset_value_list AS tracker_changeset_value_assigned_to
            ON (
                tracker_changeset_value_assigned_to.changeset_value_id = changeset_value_assigned_to.id
            )
            WHERE $values_condition
              AND changeset_value_assigned_to.field_id = equal_assigned_to_field.field_id
        )";
        $where_parameters = $values_condition->values();

        return new ParametrizedFromWhere(
            $from,
            $where,
            $from_parameters,
            $where_parameters
        );
    }

    private function getUserIdsByUserNames($values)
    {
        $user_ids = [];
        foreach ($values as $username) {
            $user_ids[] = $this->user_manager->getUserByUserName($username)->getId();
        }

        return $user_ids;
    }
}
