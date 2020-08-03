<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\MonoMilestone;

use DataAccessObject;
use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;

class MonoMilestoneBacklogItemDao extends DataAccessObject
{
    /**
     * @return LegacyDataAccessResultInterface|false
     */
    public function getTopBacklogArtifactsWithLimitAndOffset(array $backlog_tracker_ids, ?int $limit, ?int $offset)
    {
        $filter = 'AND (
                        SS.field_id IS NULL -- Use the status semantic only if it is defined
                        OR
                        CVL2.bindvalue_id = SS.open_value_id
                    )';
        return $this->getTopBacklogArtifactsWithWhereConditionAndLimitAndOffset($backlog_tracker_ids, $limit, $offset, $filter);
    }

    /**
     * @return LegacyDataAccessResultInterface|false
     */
    public function getTopBacklogOpenClosedArtifactsWithLimitAndOffset(array $backlog_tracker_ids, ?int $limit, ?int $offset)
    {
        return $this->getTopBacklogArtifactsWithWhereConditionAndLimitAndOffset($backlog_tracker_ids, $limit, $offset, '');
    }

    /**
     * @return LegacyDataAccessResultInterface|false
     */
    private function getTopBacklogArtifactsWithWhereConditionAndLimitAndOffset(array $backlog_tracker_ids, ?int $limit, ?int $offset, string $filter)
    {
        $backlog_tracker_ids = $this->da->escapeIntImplode($backlog_tracker_ids);
        $limit               = $this->da->escapeInt($limit);
        $offset              = $this->da->escapeInt($offset);
        $type_is_child       = $this->da->quoteSmart(Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD);

        $sql = "SELECT SQL_CALC_FOUND_ROWS art_1.*
                FROM tracker_artifact AS art_1
                    INNER JOIN tracker_artifact_priority_rank ON (tracker_artifact_priority_rank.artifact_id = art_1.id)
                        -- Open status section
                    INNER JOIN tracker AS T              ON (art_1.tracker_id = T.id)
                    INNER JOIN groups AS G               ON (G.group_id = T.group_id)
                    INNER JOIN tracker_changeset AS C    ON (art_1.last_changeset_id = C.id)
                    -- Look if there is any status /open/ semantic defined
                    LEFT JOIN (
                        tracker_semantic_status as SS
                        INNER JOIN tracker_changeset_value AS CV3       ON (SS.field_id = CV3.field_id)
                        INNER JOIN tracker_changeset_value_list AS CVL2 ON (CV3.id = CVL2.changeset_value_id)
                    ) ON (T.id = SS.tracker_id AND C.id = CV3.changeset_id)
                    -- ensure that the artifact is not planned in a milestone by joins + IS NULL (below)
                    LEFT JOIN (    tracker_artifact                     milestone_art
                        INNER JOIN tracker_field                        milestone_field   ON (milestone_field.tracker_id = milestone_art.tracker_id AND milestone_field.formElement_type = 'art_link' AND use_it = 1)
                        INNER JOIN tracker_changeset_value              cv_milestone      ON (cv_milestone.changeset_id = milestone_art.last_changeset_id AND cv_milestone.field_id = milestone_field.id)
                        INNER JOIN tracker_changeset_value_artifactlink artlink_milestone ON (artlink_milestone.changeset_value_id = cv_milestone.id)
                        INNER JOIN tracker_artifact                     content_art       ON (content_art.id = artlink_milestone.artifact_id)
                        INNER JOIN plugin_agiledashboard_planning       planning          ON (planning.planning_tracker_id = milestone_art.tracker_id)
                    ) ON (art_1.id = content_art.id )
                    -- ensure that the artifact is not child of anything by joins + IS NULL (below)
                    LEFT JOIN (    tracker_artifact                     parent_art
                        INNER JOIN tracker_field                        parent_field   ON (
                            parent_field.tracker_id = parent_art.tracker_id
                            AND parent_field.formElement_type = 'art_link'
                            AND use_it = 1
                            AND parent_art.tracker_id IN($backlog_tracker_ids)
                        )
                        INNER JOIN tracker_changeset_value              cv_parent      ON (cv_parent.changeset_id = parent_art.last_changeset_id AND cv_parent.field_id = parent_field.id)
                        INNER JOIN tracker_changeset_value_artifactlink artlink_parent ON (artlink_parent.changeset_value_id = cv_parent.id AND artlink_parent.nature = $type_is_child)
                        INNER JOIN tracker_artifact                     child_art      ON (child_art.id = artlink_parent.artifact_id)
                    ) ON (art_1.id = child_art.id )
                WHERE art_1.tracker_id IN ($backlog_tracker_ids)
                    $filter
                    AND content_art.id IS NULL
                    AND child_art.id IS NULL
                ORDER BY tracker_artifact_priority_rank.rank ASC
                LIMIT $limit OFFSET $offset";

        return $this->retrieve($sql);
    }
}
