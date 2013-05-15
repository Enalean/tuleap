<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

class AgileDashboard_BacklogItemDao extends DataAccessObject {

    public function getBacklogArtifacts($milestone_artifact_id) {
        $milestone_artifact_id = $this->da->escapeInt($milestone_artifact_id);
        $sql = "SELECT child_art.*
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     child_art  ON (child_art.id = artlink.artifact_id)
                    INNER JOIN plugin_agiledashboard_planning       planning   ON (planning.planning_tracker_id = parent_art.tracker_id)
                    INNER JOIN plugin_agiledashboard_planning_backlog_tracker backlog ON (backlog.planning_id = planning.id AND child_art.tracker_id = backlog.tracker_id)
                    INNER JOIN tracker_artifact_priority                       ON (tracker_artifact_priority.curr_id = child_art.id)
                WHERE parent_art.id = $milestone_artifact_id
                ORDER BY tracker_artifact_priority.rank ASC";
        return $this->retrieve($sql);

    }
}

?>
