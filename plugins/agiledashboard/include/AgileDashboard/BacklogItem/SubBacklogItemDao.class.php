<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class AgileDashboard_BacklogItem_SubBacklogItemDao extends DataAccessObject
{

    public function getAllBacklogItemIdInMilestone($milestone_id, array $parent_backlog_tracker_ids)
    {
        $select_fragments = $this->getSelectFragments($parent_backlog_tracker_ids);
        $from_fragments   = $this->getFromFragments($milestone_id, $parent_backlog_tracker_ids);

        $sql = "SELECT $select_fragments
                FROM $from_fragments";

        return $this->retrieve($sql);
    }

    private function getSelectFragments(array $list_of_trackers_ids)
    {
        $tracker_id = end($list_of_trackers_ids);
        return "GROUP_CONCAT(backlog_item_{$tracker_id}.id) AS list_of_ids";
    }

    private function getFromFragments($milestone_id, array $list_of_trackers_ids)
    {
        $trackers_ids              = $list_of_trackers_ids;
        $milestone_backlog_item_id = array_shift($trackers_ids);

        $from = "tracker_artifact AS milestone
            INNER JOIN tracker_changeset_value AS milestone_cv
              ON (milestone.last_changeset_id = milestone_cv.changeset_id AND milestone.id = $milestone_id)
            INNER JOIN tracker_changeset_value_artifactlink AS milestone_links
              ON (milestone_cv.id = milestone_links.changeset_value_id)
            INNER JOIN tracker_artifact AS backlog_item_{$milestone_backlog_item_id}
              ON (milestone_links.artifact_id = backlog_item_{$milestone_backlog_item_id}.id
                AND backlog_item_{$milestone_backlog_item_id}.tracker_id = {$milestone_backlog_item_id})
            ";

        $from .= $this->joinRecursively($milestone_backlog_item_id, $trackers_ids);

        return $from;
    }

    private function joinRecursively($parent_tracker_id, array $trackers_ids)
    {
        $child_tracker_id = array_shift($trackers_ids);
        if (! $child_tracker_id) {
            return '';
        }

        return "INNER JOIN tracker_changeset_value AS backlog_item_{$parent_tracker_id}_cv
              ON (backlog_item_{$parent_tracker_id}.last_changeset_id = backlog_item_{$parent_tracker_id}_cv.changeset_id)
            INNER JOIN tracker_changeset_value_artifactlink AS backlog_item_{$parent_tracker_id}_links
              ON (backlog_item_{$parent_tracker_id}_cv.id = backlog_item_{$parent_tracker_id}_links.changeset_value_id)
            INNER JOIN tracker_artifact AS backlog_item_{$child_tracker_id}
              ON (backlog_item_{$parent_tracker_id}_links.artifact_id = backlog_item_{$child_tracker_id}.id
                AND backlog_item_{$child_tracker_id}.tracker_id = {$child_tracker_id})

            " . $this->joinRecursively($child_tracker_id, $trackers_ids);
    }
}
