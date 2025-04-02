<?php
/**
 * Copyright (c) Enalean, 2013 – Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone;

use Tuleap\AgileDashboard\Milestone\Criterion\Status\ISearchOnStatus;
use Tuleap\AgileDashboard\Milestone\Request\SiblingMilestoneRequest;
use Tuleap\AgileDashboard\Milestone\Request\SubMilestoneRequest;
use Tuleap\AgileDashboard\Milestone\Request\TopMilestoneRequest;
use Tuleap\DB\DataAccessObject;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;

class MilestoneDao extends DataAccessObject
{
    public function searchPaginatedSubMilestones(int $milestone_artifact_id, SubMilestoneRequest $request): array
    {
        $limit  = $request->getLimit();
        $offset = $request->getOffset();
        $order  = $request->getOrder();
        $params = [$milestone_artifact_id, ArtifactLinkField::TYPE_IS_CHILD];

        $limit_statement = '';
        if ($limit > 0) {
            $limit_statement = 'LIMIT ? OFFSET ?';
            $params[]        = $limit;
            $params[]        = $offset;
        }

        if (strtolower($order) !== 'asc') {
            $order = 'desc';
        }

        [$from_status_statement, $where_status_statement] = $this->getStatusStatements(
            $request->getStatusQuery(),
            'submilestones'
        );

        $sql = <<<SQL
        SELECT SQL_CALC_FOUND_ROWS submilestones.*
        FROM tracker_artifact AS parent
            INNER JOIN tracker_field AS f ON (
                parent.tracker_id = f.tracker_id
                AND parent.id = ?
                AND f.formElement_type = 'art_link'
            )
            INNER JOIN tracker_changeset_value AS cv ON (
                cv.field_id = f.id
                AND cv.changeset_id = parent.last_changeset_id
            )
            INNER JOIN tracker_changeset_value_artifactlink AS cva ON (
                cva.changeset_value_id = cv.id
                AND cva.nature = ?
            )
            INNER JOIN tracker_artifact AS submilestones ON (
                submilestones.id = cva.artifact_id
            )
            INNER JOIN tracker AS submilestones_tracker ON (
                submilestones_tracker.id = submilestones.tracker_id
            )
            INNER JOIN plugin_agiledashboard_planning AS planning ON (
                planning.planning_tracker_id = submilestones.tracker_id
            )
            $from_status_statement

        WHERE submilestones_tracker.deletion_date IS NULL
          AND $where_status_statement

        ORDER BY submilestones.id $order
        $limit_statement
        SQL;

        return $this->getDB()->run($sql, ...$params);
    }

    public function searchPaginatedSiblingTopMilestones(int $milestone_id, int $tracker_id, SiblingMilestoneRequest $request): array
    {
        $limit  = $request->getLimit();
        $offset = $request->getOffset();

        [$from_status_statement, $where_status_statement] = $this->getStatusStatements($request->getStatusQuery(), 'art_sibling');

        $sql = <<<SQL
        SELECT SQL_CALC_FOUND_ROWS art_sibling.*
        FROM tracker_artifact AS art_sibling
            $from_status_statement

        WHERE art_sibling.id <> ?
          AND art_sibling.tracker_id = ?
          AND $where_status_statement

        ORDER BY art_sibling.id DESC

        LIMIT ? OFFSET ?
        SQL;

        return $this->getDB()->run($sql, $milestone_id, $tracker_id, $limit, $offset);
    }

    public function searchPaginatedSiblingMilestones(int $milestone_id, SiblingMilestoneRequest $request): array
    {
        $limit  = $request->getLimit();
        $offset = $request->getOffset();

        [$from_status_statement, $where_status_statement] = $this->getStatusStatements($request->getStatusQuery(), 'art_sibling');

        $sql = <<<SQL
        SELECT SQL_CALC_FOUND_ROWS art_sibling.*
        FROM tracker_artifact parent_art
            /* connect parent to its children */
            INNER JOIN tracker_field                        f_sibling         ON (f_sibling.tracker_id = parent_art.tracker_id AND f_sibling.formElement_type = 'art_link' AND f_sibling.use_it = 1)
            INNER JOIN tracker_changeset_value              cv_sibling        ON (cv_sibling.changeset_id = parent_art.last_changeset_id AND cv_sibling.field_id = f_sibling.id)
            INNER JOIN tracker_changeset_value_artifactlink artlink_sibling   ON (artlink_sibling.changeset_value_id = cv_sibling.id)
            INNER JOIN tracker_artifact                     art_sibling       ON (art_sibling.id = artlink_sibling.artifact_id)
            INNER JOIN tracker_hierarchy                    hierarchy_sibling ON (hierarchy_sibling.child_id = art_sibling.tracker_id AND hierarchy_sibling.parent_id = parent_art.tracker_id)

            /* connect child to its parent */
            INNER JOIN tracker_field                        f_child         ON (f_child.tracker_id = parent_art.tracker_id AND f_child.formElement_type = 'art_link' AND f_child.use_it = 1)
            INNER JOIN tracker_changeset_value              cv_child        ON (cv_child.changeset_id = parent_art.last_changeset_id AND cv_child.field_id = f_child.id)
            INNER JOIN tracker_changeset_value_artifactlink artlink_child   ON (artlink_child.changeset_value_id = cv_child.id)
            INNER JOIN tracker_artifact                     art_child       ON (art_child.id = artlink_child.artifact_id)
            INNER JOIN tracker_hierarchy                    hierarchy_child ON (hierarchy_child.child_id = art_child.tracker_id AND hierarchy_child.parent_id = parent_art.tracker_id)

            $from_status_statement

        WHERE art_child.id = ?
          AND art_sibling.id != art_child.id
          AND $where_status_statement

        ORDER BY art_sibling.id DESC

        LIMIT ? OFFSET ?
        SQL;

        return $this->getDB()->run($sql, $milestone_id, $limit, $offset);
    }

    public function searchPaginatedTopMilestones(int $milestone_tracker_id, TopMilestoneRequest $request): array
    {
        $order = $request->getOrder();
        if (strtolower($order) !== 'asc') {
            $order = 'desc';
        }

        $limit           = $request->getLimit();
        $offset          = $request->getOffset();
        $limit_statement = '';
        if ($limit > 0) {
            $limit_statement = 'LIMIT ? OFFSET ?';
        }

        [$from_status_statement, $where_status_statement] = $this->getStatusStatements($request->getStatusFilter(), 'submilestones');

        $sql = <<<SQL
        SELECT SQL_CALC_FOUND_ROWS submilestones.*
        FROM tracker_artifact AS submilestones
            $from_status_statement

        WHERE submilestones.tracker_id = ?
          AND $where_status_statement

        ORDER BY submilestones.id $order
        $limit_statement
        SQL;

        return $this->getDB()->run($sql, $milestone_tracker_id, $limit, $offset);
    }

    /**
     * @psalm-return array{0:string, 1:string}
     */
    private function getStatusStatements(ISearchOnStatus $criterion, string $alias_name): array
    {
        $from_status_statement  = '';
        $where_status_statement = '1';
        if (! $criterion->shouldRetrieveOpenMilestones() || ! $criterion->shouldRetrieveClosedMilestones()) {
            if ($criterion->shouldRetrieveOpenMilestones()) {
                $from_status_statement  = <<<SQL
                INNER JOIN tracker_changeset AS C ON ($alias_name.last_changeset_id = C.id)
                LEFT JOIN (
                    tracker_semantic_status as SS
                    INNER JOIN tracker_changeset_value AS CV3 ON (SS.field_id = CV3.field_id)
                    INNER JOIN tracker_changeset_value_list AS CVL2 ON (CV3.id = CVL2.changeset_value_id)
                ) ON ($alias_name.tracker_id = SS.tracker_id AND C.id = CV3.changeset_id)
                SQL;
                $where_status_statement = <<<SQL
                (
                    SS.field_id IS NULL -- Use the status semantic only if it is defined
                    OR
                    CVL2.bindvalue_id = SS.open_value_id
                 )
                SQL;
            }
            if ($criterion->shouldRetrieveClosedMilestones()) {
                $from_status_statement  = <<<SQL
                INNER JOIN tracker_changeset_value AS cvs ON(
                    $alias_name.last_changeset_id = cvs.changeset_id
                )
                INNER JOIN (
                    SELECT DISTINCT tracker_id, field_id
                    FROM tracker_semantic_status
                ) AS R ON (
                    R.tracker_id   = $alias_name.tracker_id
                    AND R.field_id = cvs.field_id
                )
                INNER JOIN tracker_changeset_value_list AS cvl ON(cvl.changeset_value_id = cvs.id)
                LEFT JOIN tracker_semantic_status AS open_values ON (
                    cvl.bindvalue_id = open_values.open_value_id
                    AND open_values.tracker_id = $alias_name.tracker_id
                    AND cvs.field_id = open_values.field_id
                )
                SQL;
                $where_status_statement = 'open_values.open_value_id IS NULL';
            }
        }

        return [$from_status_statement, $where_status_statement];
    }

    public function searchSubMilestones(int $milestone_artifact_id): array
    {
        $sql = <<<SQL
        SELECT SQL_CALC_FOUND_ROWS submilestones.*
        FROM tracker_artifact AS parent
            INNER JOIN tracker_field AS f ON (
                parent.tracker_id = f.tracker_id
                AND parent.id = ?
                AND f.formElement_type = 'art_link'
            )
            INNER JOIN tracker_changeset_value AS cv ON (
                cv.field_id = f.id
                AND cv.changeset_id = parent.last_changeset_id
            )
            INNER JOIN tracker_changeset_value_artifactlink AS cva ON (
                cva.changeset_value_id = cv.id
                AND cva.nature = ?
            )
            INNER JOIN tracker_artifact AS submilestones ON (
                submilestones.id = cva.artifact_id
            )
            INNER JOIN tracker AS submilestones_tracker ON (
                submilestones_tracker.id = submilestones.tracker_id
            )
            INNER JOIN plugin_agiledashboard_planning AS planning ON (
                planning.planning_tracker_id = submilestones.tracker_id
            )
        WHERE submilestones_tracker.deletion_date IS NULL
        ORDER BY submilestones.id ASC
        SQL;

        return $this->getDB()->run($sql, $milestone_artifact_id, ArtifactLinkField::TYPE_IS_CHILD);
    }

    public function getAllMilestoneByTrackers(array $list_of_trackers_ids): array
    {
        $select_fragments = $this->getSelectFragments($list_of_trackers_ids);
        $from_fragments   = $this->getFromFragments($list_of_trackers_ids);
        $order_fragments  = $this->getOrderFragments($list_of_trackers_ids);

        $sql = "SELECT $select_fragments
                FROM $from_fragments
                ORDER BY $order_fragments";

        return $this->getDB()->run($sql, ...$list_of_trackers_ids);
    }

    private function getOrderFragments(array $list_of_trackers_ids): string
    {
        return 'm' . implode('.id, m', $list_of_trackers_ids) . '.id';
    }

    private function getSelectFragments(array $list_of_trackers_ids): string
    {
        return implode(
            ', ',
            array_map(
                fn(int $tracker_id) => $this->extractSelectFragments($tracker_id),
                $list_of_trackers_ids
            )
        );
    }

    private function extractSelectFragments(int $tracker_id): string
    {
        return "m$tracker_id.id as m{$tracker_id}_id, m{$tracker_id}_CVT.value AS m{$tracker_id}_title";
    }

    private function getFromFragments(array $list_of_trackers_ids): string
    {
        $trackers_ids     = $list_of_trackers_ids;
        $first_tracker_id = array_shift($trackers_ids);
        return "tracker_artifact AS m{$first_tracker_id}
                {$this->getTrackerFromFragment($first_tracker_id)}
                {$this->getTitleFromFragment($first_tracker_id)}
                {$this->joinRecursively($first_tracker_id, $trackers_ids)}";
    }

    private function joinRecursively(int $parent_tracker_id, array $trackers_ids): string
    {
        $child_tracker_id = array_shift($trackers_ids);
        if ($child_tracker_id === null) {
            return '';
        }

        return "LEFT JOIN (
            tracker_changeset_value AS m{$parent_tracker_id}_CV2
            INNER JOIN tracker_changeset_value_artifactlink AS m{$parent_tracker_id}_AL ON ( m{$parent_tracker_id}_CV2.id = m{$parent_tracker_id}_AL.changeset_value_id )
            INNER JOIN tracker_artifact AS m$child_tracker_id ON (m{$parent_tracker_id}_AL.artifact_id = m$child_tracker_id.id)
            {$this->getTrackerFromFragment($child_tracker_id)}
            {$this->joinRecursively($child_tracker_id, $trackers_ids)}
            {$this->getTitleFromFragment($child_tracker_id)}
        ) ON (m$parent_tracker_id.last_changeset_id = m{$parent_tracker_id}_CV2.changeset_id)";
    }

    private function getTitleFromFragment(int $tracker_id): string
    {
        return "LEFT JOIN (
            tracker_changeset_value AS m{$tracker_id}_CV
            INNER JOIN tracker_semantic_title AS m{$tracker_id}_ST ON ( m{$tracker_id}_CV.field_id = m{$tracker_id}_ST.field_id )
            INNER JOIN tracker_changeset_value_text AS m{$tracker_id}_CVT ON ( m{$tracker_id}_CV.id = m{$tracker_id}_CVT.changeset_value_id )
        ) ON (m$tracker_id.last_changeset_id = m{$tracker_id}_CV.changeset_id)";
    }

    private function getTrackerFromFragment(int $tracker_id): string
    {
        return "INNER JOIN tracker AS mt$tracker_id ON (mt$tracker_id.id = m$tracker_id.tracker_id AND m$tracker_id.tracker_id = ?)";
    }

    public function countMilestones(): int
    {
        $sql = <<<SQL
        SELECT count(*) AS nb
        FROM plugin_agiledashboard_planning AS planning
        INNER JOIN tracker_hierarchy AS hierarchy
            ON planning.planning_tracker_id = hierarchy.parent_id
        INNER JOIN tracker_artifact AS artifact
            ON hierarchy.parent_id = artifact.tracker_id
        INNER JOIN tracker
            ON artifact.tracker_id = tracker.id
        WHERE tracker.deletion_date IS NULL
        SQL;

        return $this->getDB()->single($sql);
    }

    public function countMilestonesAfter(int $timestamp): int
    {
        $sql = <<<SQL
        SELECT count(*) AS nb
        FROM plugin_agiledashboard_planning AS planning
        INNER JOIN tracker_hierarchy AS hierarchy
            ON planning.planning_tracker_id = hierarchy.parent_id
        INNER JOIN tracker_artifact AS artifact
            ON hierarchy.parent_id = artifact.tracker_id
            AND artifact.submitted_on > ?
        INNER JOIN tracker
            ON artifact.tracker_id = tracker.id
        WHERE tracker.deletion_date IS NULL
        SQL;

        return $this->getDB()->single($sql, [$timestamp]);
    }
}
