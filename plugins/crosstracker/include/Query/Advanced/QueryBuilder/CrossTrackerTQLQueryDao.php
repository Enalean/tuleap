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

declare(strict_types=1);

namespace Tuleap\CrossTracker\Query\Advanced\QueryBuilder;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\CrossTracker\Query\Advanced\OrderByBuilder\ParametrizedFromOrder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\IProvideParametrizedSelectAndFromAndWhereSQLFragments;
use Tuleap\DB\DataAccessObject;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;

final class CrossTrackerTQLQueryDao extends DataAccessObject
{
    public function getTrackersIdsForForwardLink(int $artifact_id): array
    {
        return $this->getDB()->col(
            <<<EOS
            SELECT DISTINCT link.tracker_id AS id
            FROM tracker_artifact AS link
                INNER JOIN tracker_changeset_value AS TCV
                    ON (link.last_changeset_id = TCV.changeset_id)
                INNER JOIN tracker_changeset_value_artifactlink AS TCVAL
                    ON (TCVAL.changeset_value_id = TCV.id AND TCVAL.artifact_id = ?)
                INNER JOIN tracker
                    ON (tracker.id = link.tracker_id AND tracker.deletion_date IS NULL)
                INNER JOIN `groups` AS project
                    ON (project.group_id = tracker.group_id AND project.status = 'A')
            EOS,
            0,
            $artifact_id
        );
    }

    public function getTrackersIdsForReverseLink(int $artifact_id): array
    {
        return $this->getDB()->col(
            <<<EOS
            SELECT DISTINCT link.tracker_id AS id
            FROM tracker_artifact AS source
                INNER JOIN tracker_changeset_value AS TCV
                    ON (source.id = ? AND source.last_changeset_id = TCV.changeset_id)
                INNER JOIN tracker_changeset_value_artifactlink AS TCVAL
                    ON (TCVAL.changeset_value_id = TCV.id)
                INNER JOIN tracker_artifact AS link
                    ON (TCVAL.artifact_id = link.id)
                INNER JOIN tracker
                    ON (tracker.id = link.tracker_id AND tracker.deletion_date IS NULL)
                INNER JOIN `groups` AS project
                    ON (project.group_id = tracker.group_id AND project.status = 'A')
            EOS,
            0,
            $artifact_id
        );
    }

    /**
     * @return list<array{id: int}>
     */
    public function searchArtifactsIdsMatchingQuery(
        IProvideParametrizedFromAndWhereSQLFragments $from_where,
        ParametrizedFromOrder $from_order,
        array $tracker_ids,
    ): array {
        $from  = $from_where->getFrom() . ' ' . $from_order->getFrom();
        $where = $from_where->getWhere();
        $order = $from_order->getOrderBy();

        if ($order === '') {
            $order = 'artifact.id DESC';
        }

        $tracker_ids_statement = EasyStatement::open()->in('artifact.tracker_id IN (?*)', $tracker_ids);

        $sql = <<<EOSQL
        SELECT DISTINCT artifact.id AS id
        FROM tracker_artifact as artifact
        INNER JOIN tracker ON (artifact.tracker_id = tracker.id)
        INNER JOIN tracker_changeset AS changeset ON (artifact.last_changeset_id = changeset.id)
        $from
        WHERE $tracker_ids_statement AND $where
        ORDER BY $order
        EOSQL;

        $parameters = [
            ...$from_where->getFromParameters(),
            ...$from_order->getFromParameters(),
            ...$tracker_ids_statement->values(),
            ...$from_where->getWhereParameters(),
        ];

        return $this->getDB()->q($sql, ...$parameters);
    }

    /**
     * @param list<IProvideParametrizedSelectAndFromAndWhereSQLFragments> $select_from_fragments,
     * @param list<int> $artifact_ids
     */
    public function searchArtifactsColumnsMatchingIds(
        array $select_from_fragments,
        ParametrizedFromOrder $from_order,
        array $artifact_ids,
    ): array {
        $results = [];
        foreach ($select_from_fragments as $select_from) {
            $select           = $select_from->getSelect();
            $from             = $select_from->getFrom() . ' ' . $from_order->getFrom();
            $order            = $from_order->getOrderBy();
            $condition        = '';
            $where_parameters = [];

            $select_from->getWhere()
                ->match(
                    function ($where) use (&$condition, &$where_parameters, $select_from) {
                        $condition        = $where;
                        $where_parameters = $select_from->getWhereParameters();
                    },
                    function () use (&$condition, &$where_parameters, $artifact_ids) {
                        $condition        = EasyStatement::open()->in('artifact.id IN (?*)', $artifact_ids);
                        $where_parameters = $artifact_ids;
                    }
                );

            if ($select !== '') {
                $select = ', ' . $select;
            }

            if ($order === '') {
                $order = 'artifact.id DESC';
            }

            $sql = <<<SQL
            SELECT DISTINCT artifact.id
            $select
            FROM tracker_artifact AS artifact
            INNER JOIN tracker ON (artifact.tracker_id = tracker.id)
            INNER JOIN tracker_changeset AS changeset ON (changeset.id = artifact.last_changeset_id)
            $from
            WHERE $condition
            ORDER BY $order
            SQL;

            $parameters = [
                ...$select_from->getFromParameters(),
                ...$from_order->getFromParameters(),
                ...$where_parameters,
            ];

            $results = $this->mergeArtifactColumns($results, $this->getDB()->q($sql, ...$parameters));
        }
        return $results;
    }

    private function mergeArtifactColumns(array $results, array $column_results): array
    {
        foreach ($column_results as $artifact_column) {
            $id = $artifact_column['id'];
            if (isset($results[$id])) {
                foreach ($artifact_column as $name => $value) {
                    if ($name === 'id') {
                        continue;
                    }
                    if (isset($results[$id][$name])) {
                        if (is_array($results[$id][$name])) {
                            $results[$id][$name][] = $value;
                        } else {
                            $results[$id][$name] = [$results[$id][$name], $value];
                        }
                    } else {
                        $results[$id][$name] = $value;
                    }
                }
            } else {
                $results[$id] = $artifact_column;
            }
        }

        return $results;
    }

    /**
     * @return list<array{id:int}>
     */
    public function searchTrackersIdsMatchingQuery(
        IProvideParametrizedFromAndWhereSQLFragments $from_where,
        int $limit,
    ): array {
        $sql_limit  = '';
        $parameters = [];
        if ($limit > 0) {
            $sql_limit    = 'LIMIT ?';
            $parameters[] = $limit;
        }

        $from  = $from_where->getFrom();
        $where = $from_where->getWhere();

        if ($where === '') {
            $where = '1=1';
        }

        $sql = <<<SQL
        SELECT tracker.id
        FROM tracker
        INNER JOIN `groups` AS project ON (project.group_id = tracker.group_id)
        $from
        WHERE tracker.deletion_date IS NULL AND project.status = 'A'
            AND $where
        $sql_limit
        GROUP BY tracker.id
        SQL;

        $parameters = [
            ...$from_where->getFromParameters(),
            ...$from_where->getWhereParameters(),
            ...$parameters,
        ];

        return $this->getDB()->q($sql, ...$parameters);
    }
}
