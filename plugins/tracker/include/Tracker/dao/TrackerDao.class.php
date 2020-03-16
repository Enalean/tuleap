<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Tracker\Hierarchy\HierarchyDAO;

class TrackerDao extends DataAccessObject
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker';
    }

    public function searchById($id)
    {
        $id      = $this->da->escapeInt($id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE id = $id ";
        return $this->retrieve($sql);
    }

    public function searchByGroupId($group_id)
    {
        $group_id = $this->da->escapeInt($group_id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE group_id = $group_id
                  AND deletion_date IS NULL
                ORDER BY name";
        return $this->retrieve($sql);
    }

    /**
     * searches trackers by group_id, excluding some given trackers
     */
    public function searchByGroupIdWithExcludedIds($group_id, array $excluded_tracker_ids)
    {
        $group_id             = $this->da->escapeInt($group_id);
        $excluded_clause = $this->restrict("AND id NOT IN", $excluded_tracker_ids);

        // TODO: escape $excluded_tracker_ids ?

        $sql = "SELECT *
                FROM $this->table_name
                WHERE group_id = $group_id
                  AND deletion_date IS NULL
                  $excluded_clause
                ORDER BY name";

        return $this->retrieve($sql);
    }

   /**
    * Check if the shortname of the tracker is already used in the project
    * @param string $item_name the shortname of the tracker we are looking for
    * @param int $group_id the ID of the group
    * @return bool
    */
    public function isShortNameExists($item_name, $group_id)
    {
        $item_name = $this->da->quoteSmart($item_name);
        $group_id  = $this->da->escapeInt($group_id);

        $search_tv3 = '';
        if (TrackerV3::instance()->available()) {
            $search_tv3 = "UNION
                  SELECT item_name
                  FROM artifact_group_list
                  WHERE item_name = $item_name
                    AND group_id = $group_id
                    AND deletion_date IS NULL";
        }

        $sql = "SELECT item_name
                FROM $this->table_name
                WHERE item_name = $item_name
                  AND group_id = $group_id
                  AND deletion_date IS NULL
                " . $search_tv3;
        return count($this->retrieve($sql))  > 0;
    }

   /**
    * Retrieve the Tracker with the specified item_name from the Project with the given ID
    * @param string $item_name the shortname of the tracker we are looking for
    * @param int $project_id the ID of the project
    * @return DataAccessResult
    */
    public function searchByItemNameAndProjectId($item_name, $project_id)
    {
        $item_name = $this->da->quoteSmart($item_name);
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT *
                FROM $this->table_name
                WHERE item_name = $item_name
                  AND group_id = $project_id
                  AND deletion_date IS NULL";
        return $this->retrieve($sql);
    }

    public function doesTrackerNameAlreadyExist(string $public_name, int $project_id): bool
    {
        $public_name = $this->da->quoteSmart($public_name);
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT *
                FROM tracker
                WHERE name = $public_name
                  AND group_id = $project_id
                  AND deletion_date IS NULL";
        $res = $this->retrieve($sql);
        return $res && count($res) > 0;
    }

    public function markAsDeleted($id)
    {
        $id = $this->da->escapeInt($id);
        $deletion_date = $this->da->escapeInt($_SERVER['REQUEST_TIME']);
        $sql = "UPDATE $this->table_name
                SET deletion_date = $deletion_date
                WHERE id = $id";

        if ($this->update($sql)) {
            $hierarchy_dao = new HierarchyDAO();
            $hierarchy_dao->deleteParentChildAssociationsForTracker($id);
            return true;
        } else {
            return false;
        }
    }

    public function duplicate($atid_template, $group_id, $name, $description, $item_name, ?string $color)
    {
        $atid_template = $this->da->escapeInt($atid_template);
        $group_id      = $this->da->escapeInt($group_id);
        $name          = $this->da->quoteSmart($name);
        $description   = $this->da->quoteSmart($description);
        $item_name     = $this->da->quoteSmart($item_name);

        $id_sharing = new TrackerIdSharingDao();
        $id         = $id_sharing->generateTrackerId();
        if (! $id) {
            return false;
        }

        $insert = "INSERT INTO tracker
                       (id,
                        group_id,
                        name,
                        description,
                        item_name,
                        instantiate_for_new_projects,
                        log_priority_changes,
                        allow_copy,
                        submit_instructions,
                        browse_instructions,
                        status,
                        notifications_level,
                        color,
                        enable_emailgateway)
                    ";
        if ($color) {
            $color_name  = $this->da->quoteSmart($color);
            $from_select = "SELECT
                        $id,
                        $group_id,
                        $name,
                        $description,
                        $item_name,
                        1,
                        log_priority_changes,
                        allow_copy,
                        submit_instructions,
                        browse_instructions,
                        status,
                        notifications_level,
                        $color_name,
                        enable_emailgateway
                    FROM tracker
                    WHERE id = $atid_template";
        } else {
            $from_select = "SELECT
                        $id,
                        $group_id,
                        $name,
                        $description,
                        $item_name,
                        1,
                        log_priority_changes,
                        allow_copy,
                        submit_instructions,
                        browse_instructions,
                        status,
                        notifications_level,
                        color,
                        enable_emailgateway
                    FROM tracker
                    WHERE id = $atid_template";
        }

        $sql = $insert . $from_select;
        if ($this->update($sql)) {
            return $id;
        }

        return false;
    }

    public function create(
        $group_id,
        $name,
        $description,
        $item_name,
        $allow_copy,
        $submit_instructions,
        $browse_instructions,
        $status,
        $deletion_date,
        $instantiate_for_new_projects,
        $log_priority_changes,
        $notifications_level,
        $color,
        $enable_emailgateway
    ) {
        $group_id                     = $this->da->escapeInt($group_id);
        $name                         = $this->da->quoteSmart($name);
        $description                  = $this->da->quoteSmart($description);
        $item_name                    = $this->da->quoteSmart($item_name);
        $allow_copy                   = $this->da->escapeInt($allow_copy);
        $enable_emailgateway          = $this->da->escapeInt($enable_emailgateway);
        $submit_instructions          = $this->da->quoteSmart($submit_instructions);
        $browse_instructions          = $this->da->quoteSmart($browse_instructions);
        $status                       = $this->da->quoteSmart($status);
        $deletion_date                = $deletion_date ? $this->da->escapeInt($deletion_date) : 'NULL';
        $instantiate_for_new_projects = $this->da->quoteSmart($instantiate_for_new_projects);
        $log_priority_changes         = $this->da->quoteSmart($log_priority_changes);
        $notifications_level          = $this->da->escapeInt($notifications_level);
        $color                        = $this->da->quoteSmart($color);

        $id_sharing = new TrackerIdSharingDao();
        if ($id = $id_sharing->generateTrackerId()) {
            $sql = "INSERT INTO $this->table_name
                    (id,
                        group_id,
                        name,
                        description,
                        item_name,
                        allow_copy,
                        submit_instructions,
                        browse_instructions,
                        status,
                        deletion_date,
                        instantiate_for_new_projects,
                        log_priority_changes,
                        notifications_level,
                        color,
                        enable_emailgateway)
                    VALUES ($id,
                        $group_id,
                        $name,
                        $description,
                        $item_name,
                        $allow_copy,
                        $submit_instructions,
                        $browse_instructions,
                        $status,
                        $deletion_date,
                        $instantiate_for_new_projects,
                        $log_priority_changes,
                        $notifications_level,
                        $color,
                        $enable_emailgateway)";
            if ($this->update($sql)) {
                return $id;
            }
        }
        return false;
    }

    public function save(Tracker $tracker)
    {
        $id                  = $this->da->escapeInt($tracker->id);
        $group_id                     = $this->da->escapeInt($tracker->group_id);
        $name                         = $this->da->quoteSmart($tracker->name);
        $description                  = $this->da->quoteSmart($tracker->description);
        $color                        = $this->da->quoteSmart($tracker->getColor()->getName());
        $item_name                    = $this->da->quoteSmart($tracker->item_name);
        $allow_copy                   = $this->da->escapeInt($tracker->allow_copy);
        $enable_emailgateway          = $this->da->escapeInt($tracker->isEmailgatewayEnabled());
        $submit_instructions          = $this->da->quoteSmart($tracker->submit_instructions);
        $browse_instructions          = $this->da->quoteSmart($tracker->browse_instructions);
        $status                       = $this->da->quoteSmart($tracker->status);
        $deletion_date                = $tracker->deletion_date ? $this->da->escapeInt($tracker->deletion_date) : 'NULL';
        $instantiate_for_new_projects = $this->da->quoteSmart($tracker->instantiate_for_new_projects);
        $log_priority_changes         = $this->da->quoteSmart($tracker->log_priority_changes);
        $notifications_level          = $this->da->escapeInt($tracker->getNotificationsLevel());
        $sql = "UPDATE $this->table_name SET
                   group_id                     = $group_id,
                   name                         = $name,
                   description                  = $description,
                   color                        = $color,
                   item_name                    = $item_name,
                   allow_copy                   = $allow_copy,
                   submit_instructions          = $submit_instructions,
                   browse_instructions          = $browse_instructions,
                   status                       = $status,
                   deletion_date                = $deletion_date,
                   instantiate_for_new_projects = $instantiate_for_new_projects,
                   log_priority_changes         = $log_priority_changes,
                   notifications_level          = $notifications_level,
                   enable_emailgateway          = $enable_emailgateway
                WHERE id = $id ";
        return $this->update($sql);
    }

    public function delete($id)
    {
        $sql = "DELETE FROM $this->table_name WHERE id = " . $this->da->escapeInt($id);
        return $this->update($sql);
    }

    public function updateItemName($group_id, $oldItemname, $itemname)
    {
        $group_id = $this->da->quoteSmart($group_id);
        $itemname = $this->da->quoteSmart($itemname);
        $oldItemname = $this->da->quoteSmart($oldItemname);
        $sql = "UPDATE $this->table_name SET
			item_name=$itemname
            WHERE item_name=$oldItemname AND group_id=$group_id";
        return $this->update($sql);
    }

    private function restrict($restriction_clause, $excluded_tracker_ids)
    {
        if (!$excluded_tracker_ids) {
            return "";
        }
        $id_enumeration = implode(',', $excluded_tracker_ids);
        return "$restriction_clause ($id_enumeration)";
    }

    /**
    * Searches deleted trackers
    *
    * @return DataAccessResult
    */
    public function retrieveTrackersMarkAsDeleted()
    {
        $sql = "SELECT tracker.*
                FROM tracker
                    INNER JOIN groups USING (group_id)
                WHERE tracker.deletion_date > 0
                    AND groups.status <> 'D'
                ORDER BY tracker.group_id";

        return $this->retrieve($sql);
    }

    /**
    * Restore a deleted tracker by removig its deletion_date flag.
    *
    * @param int $tracker_id the ID of the tracker
    *
    * @return bool
    */
    public function restoreTrackerMarkAsDeleted($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql        = "UPDATE $this->table_name SET
                          deletion_date = NULL
                      WHERE id = $tracker_id";
        return $this->update($sql);
    }
}
