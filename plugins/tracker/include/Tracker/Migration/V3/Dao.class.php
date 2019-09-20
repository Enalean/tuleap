<?php
/**
 * Copyright (c) Enalean, 2012 - 2016. All Rights Reserved.
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

class Tracker_Migration_V3_Dao extends DataAccessObject
{

    public function create($project_id, $name, $description, $itemname, $tv3_id)
    {
        $id_sharing = new TrackerIdSharingDao();
        if ($tv5_id = $id_sharing->generateTrackerId()) {
            $tv3_id     = $this->da->escapeInt($tv3_id);
            $tracker_id = $this->createTracker($tv5_id, $project_id, $name, $description, $itemname, $tv3_id);
            $this->duplicateTrackerPerms($tv3_id, $tracker_id);
            return $tracker_id;
        }
        return false;
    }

    private function createTracker($id, $project_id, $name, $description, $itemname, $tv3_id)
    {
        $project_id  = $this->da->escapeInt($project_id);
        $name        = $this->da->quoteSmart($name);
        $description = $this->da->quoteSmart($description);
        $itemname    = $this->da->quoteSmart($itemname);
        $sql = "INSERT INTO tracker ( id, group_id, name, description, item_name, allow_copy, submit_instructions, browse_instructions,
                                      status, deletion_date, instantiate_for_new_projects, notifications_level, from_tv3_id)
                SELECT $id, $project_id, $name, $description, $itemname, allow_copy, submit_instructions, browse_instructions,
                       status, deletion_date, instantiate_for_new_projects, stop_notification, $tv3_id
                FROM artifact_group_list
                WHERE group_artifact_id = $tv3_id";
        return $this->updateAndGetLastId($sql);
    }

    private function duplicateTrackerPerms($tv3_id, $tv5_id)
    {
        $sql = "INSERT INTO permissions(object_id, permission_type, ugroup_id)
                SELECT $tv5_id, CONCAT('PLUGIN_', permission_type), ugroup_id
                FROM permissions
                WHERE object_id = $tv3_id
                  AND permission_type LIKE 'TRACKER_ACCESS_%'";
        $this->update($sql);
    }
}
