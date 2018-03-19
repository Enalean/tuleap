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

namespace Tuleap\Velocity;

use Tuleap\DB\DataAccessObject;

class VelocityDao extends DataAccessObject
{
    public function searchPlanningLinkedArtifact($artifact_id)
    {
        $sql = 'SELECT linked_art.id AS id
                    FROM tracker_artifact
                      INNER JOIN tracker ON tracker_artifact.tracker_id = tracker.id
                      INNER JOIN tracker_changeset AS changeset
                        ON changeset.artifact_id = tracker_artifact.id
                        AND tracker_artifact.last_changeset_id = changeset.id
                      INNER JOIN tracker_field AS f
                        ON (f.tracker_id = tracker_artifact.tracker_id AND f.formElement_type = \'art_link\' AND use_it = 1)
                      INNER JOIN tracker_changeset_value AS cv
                        ON (cv.changeset_id = changeset.id AND cv.field_id = f.id)
                      INNER JOIN tracker_changeset_value_artifactlink artlink
                        ON (artlink.changeset_value_id = cv.id)
                      INNER JOIN tracker_artifact AS linked_art
                        ON (linked_art.id = artlink.artifact_id)
                      INNER JOIN plugin_agiledashboard_planning
                        ON plugin_agiledashboard_planning.planning_tracker_id = tracker_artifact.tracker_id
                      INNER JOIN plugin_agiledashboard_planning_backlog_tracker
                        ON plugin_agiledashboard_planning_backlog_tracker.planning_id = plugin_agiledashboard_planning.id
                           AND linked_art.tracker_id = plugin_agiledashboard_planning_backlog_tracker.tracker_id
                    WHERE tracker_artifact.id = ?
                      AND tracker.deletion_date IS NULL';

        return $this->getDB()->run($sql, $artifact_id);
    }
}
