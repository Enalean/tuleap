<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'common/dao/include/DataAccessObject.class.php';

class Planning_MilestoneDao extends DataAccessObject {

    public function searchBacklogItemsInMilestones($artifact_link_field_id, $backlog_tracker_id, array $milestones_artifact_ids) {
        $sql = "SELECT cv_al.artifact_id
                FROM tracker_artifact AS src_artifact
                    INNER JOIN tracker_changeset_value AS cv ON (cv.changeset_id = src_artifact.last_changeset_id AND field_id = ".$artifact_link_field_id.") -- artifact_link_id of release tracker
                    INNER JOIN tracker_changeset_value_artifactlink AS cv_al ON (cv_al.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact AS a ON (a.id = cv_al.artifact_id AND a.tracker_id = ".$backlog_tracker_id.") -- filter out only epics (backlog_tracker_id)
                    INNER JOIN tracker_artifact_priority ON (tracker_artifact_priority.curr_id = a.id)
                WHERE src_artifact.id IN (".implode(',', $milestones_artifact_ids).") -- all releases";
        return $this->retrieve($sql);
    }
}

?>
