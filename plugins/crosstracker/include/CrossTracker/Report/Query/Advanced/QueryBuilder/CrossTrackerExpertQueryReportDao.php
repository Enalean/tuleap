<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\CrossTracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;
use Tuleap\CrossTracker\Report\Query\ParametrizedFrom;
use Tuleap\DB\DataAccessObject;

final class CrossTrackerExpertQueryReportDao extends DataAccessObject
{
    public function searchArtifactsMatchingQuery(
        IProvideParametrizedFromAndWhereSQLFragments $from_where,
        array $tracker_ids,
        $limit,
        $offset,
    ) {
        $unique_parametrized_from = array_unique($from_where->getAllParametrizedFrom());

        $from  = $this->getFrom($unique_parametrized_from);
        $where = $from_where->getWhere();

        $tracker_ids_statement = EasyStatement::open()->in('tracker_artifact.tracker_id IN (?*)', $tracker_ids);

        $sql = "SELECT
                  SQL_CALC_FOUND_ROWS
                  DISTINCT
                  tracker_artifact.tracker_id,
                  tracker_artifact.id,
                  tracker_changeset_value_title.value AS title
                FROM tracker_artifact
                  INNER JOIN tracker_changeset AS last_changeset ON (tracker_artifact.last_changeset_id = last_changeset.id)
                  INNER JOIN tracker ON (tracker_artifact.tracker_id = tracker.id)
                  INNER JOIN `groups`  ON (`groups`.group_id = tracker.group_id)
                  LEFT JOIN (
                      tracker_changeset_value AS changeset_value_title
                      INNER JOIN tracker_semantic_title
                        ON (tracker_semantic_title.field_id = changeset_value_title.field_id)
                      INNER JOIN tracker_changeset_value_text AS tracker_changeset_value_title
                          ON (tracker_changeset_value_title.changeset_value_id = changeset_value_title.id)
                    ) ON (
                      tracker_semantic_title.tracker_id = tracker_artifact.tracker_id
                      AND changeset_value_title.changeset_id = tracker_artifact.last_changeset_id
                    )
                    $from
                    WHERE `groups`.status = 'A'
                      AND tracker.deletion_date IS NULL
                      AND $tracker_ids_statement
                      AND $where
                ORDER BY tracker_artifact.id DESC
                LIMIT ?, ?";

        $parameters   = $this->getFromParameters($unique_parametrized_from);
        $parameters   = array_merge($parameters, $tracker_ids_statement->values());
        $parameters   = array_merge($parameters, $from_where->getWhereParameters());
        $parameters[] = $offset;
        $parameters[] = $limit;

        return $this->getDB()->safeQuery($sql, $parameters);
    }

    private function getFrom(array $unique_parametrized_from)
    {
        return implode(
            '',
            array_map(
                function (ParametrizedFrom $parametrized_from) {
                    return $parametrized_from->getFrom();
                },
                $unique_parametrized_from
            )
        );
    }

    private function getFromParameters($unique_parametrized_from)
    {
        return array_reduce(
            $unique_parametrized_from,
            function (array $carry, ParametrizedFrom $parametrized_from) {
                return array_merge($carry, $parametrized_from->getParameters());
            },
            []
        );
    }
}
