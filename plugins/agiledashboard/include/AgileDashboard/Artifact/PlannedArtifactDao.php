<?php
/**
 * Copyright (c) Enalean 2019 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Artifact;

use Tuleap\DB\DataAccessObject;

class PlannedArtifactDao extends DataAccessObject
{
    public function isArtifactPlannedInAMilestoneOfTheProject(int $artifact_id, int $project_id): bool
    {
        $sql = "SELECT *
                FROM tracker_changeset_value_artifactlink AS artlink
                         JOIN tracker_changeset_value          AS cv ON (cv.id = artlink.changeset_value_id)
                         JOIN tracker_artifact                 AS a  ON (a.last_changeset_id = cv.changeset_id)
                         JOIN tracker                          AS t  ON (t.id = a.tracker_id)
                         JOIN plugin_agiledashboard_planning   AS p  ON (p.planning_tracker_id = t.id)
                         JOIN groups ON (groups.group_id = t.group_id)
                WHERE artlink.artifact_id = ?
                  AND groups.status = 'A'
                  AND p.group_id = ?";

        $rows = $this->getDB()->run($sql, $artifact_id, $project_id);

        return count($rows) > 0;
    }

    public function gatAllPlannedArtifactsOfTheProject(int $project_id, int $tracker_id)
    {
        $sql = "SELECT DISTINCT artlink.artifact_id
                FROM tracker_changeset_value_artifactlink AS artlink
                         JOIN tracker_artifact                 AS artlink_artifact  ON (artlink.artifact_id = artlink_artifact.id)
                         JOIN tracker_changeset_value          AS cv ON (cv.id = artlink.changeset_value_id)
                         JOIN tracker_artifact                 AS a  ON (a.last_changeset_id = cv.changeset_id)
                         JOIN tracker                          AS t  ON (t.id = a.tracker_id)
                         JOIN plugin_agiledashboard_planning   AS p  ON (p.planning_tracker_id = t.id)
                         JOIN `groups` ON (`groups`.group_id = t.group_id)
                WHERE artlink_artifact.tracker_id = ?
                  AND `groups`.status = 'A'
                  AND p.group_id = ?";

        return $this->getDB()->run($sql, $tracker_id, $project_id);
    }
}
