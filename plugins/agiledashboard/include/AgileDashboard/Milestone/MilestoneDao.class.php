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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class AgileDashboard_Milestone_MilestoneDao extends DataAccessObject {

    public function getAllMilestoneByTrackers(array $list_of_trackers_ids) {
        $select_fragments = $this->getSelectFragments($list_of_trackers_ids);
        $from_fragments   = $this->getFromFragments($list_of_trackers_ids);
        $order_fragments  = $this->getOrderFragments($list_of_trackers_ids);

        $sql = "SELECT $select_fragments
                FROM $from_fragments
                ORDER BY $order_fragments";

        return $this->retrieve($sql);
    }

    private function getOrderFragments(array $list_of_trackers_ids) {
        return 'm'. implode('.id, m', $list_of_trackers_ids) .'.id';
    }

    private function getSelectFragments(array $list_of_trackers_ids) {
        return implode(', ', array_map(array($this, 'extractSelectFragments'), $list_of_trackers_ids));
    }

    private function extractSelectFragments($tracker_id) {
        return "m{$tracker_id}.id as m{$tracker_id}_id, m{$tracker_id}_CVT.value AS m{$tracker_id}_title";
    }

    private function getFromFragments(array $list_of_trackers_ids) {
        $trackers_ids = $list_of_trackers_ids;
        $first_tracker_id = array_shift($trackers_ids);
        return "tracker_artifact AS m{$first_tracker_id}
                {$this->getTrackerFromFragment($first_tracker_id)}
                {$this->getTitleFromFragment($first_tracker_id)}
                {$this->joinRecursively($first_tracker_id, $trackers_ids)}";
    }

    private function joinRecursively($parent_tracker_id, array $trackers_ids) {
        $child_tracker_id = array_shift($trackers_ids);
        if (! $child_tracker_id) {
            return '';
        }

        return "LEFT JOIN (
            tracker_changeset_value AS m{$parent_tracker_id}_CV2
            INNER JOIN tracker_changeset_value_artifactlink AS m{$parent_tracker_id}_AL ON ( m{$parent_tracker_id}_CV2.id = m{$parent_tracker_id}_AL.changeset_value_id )
            INNER JOIN tracker_artifact AS m{$child_tracker_id} ON (m{$parent_tracker_id}_AL.artifact_id = m{$child_tracker_id}.id)
            {$this->getTrackerFromFragment($child_tracker_id)}
            {$this->joinRecursively($child_tracker_id, $trackers_ids)}
            {$this->getTitleFromFragment($child_tracker_id)}
        ) ON (m{$parent_tracker_id}.last_changeset_id = m{$parent_tracker_id}_CV2.changeset_id)";
    }

    private function getTitleFromFragment($tracker_id) {
        return "LEFT JOIN (
            tracker_changeset_value AS m{$tracker_id}_CV
            INNER JOIN tracker_semantic_title AS m{$tracker_id}_ST ON ( m{$tracker_id}_CV.field_id = m{$tracker_id}_ST.field_id )
            INNER JOIN tracker_changeset_value_text AS m{$tracker_id}_CVT ON ( m{$tracker_id}_CV.id = m{$tracker_id}_CVT.changeset_value_id )
        ) ON (m{$tracker_id}.last_changeset_id = m{$tracker_id}_CV.changeset_id)";
    }

    private function getTrackerFromFragment($tracker_id) {
        return "INNER JOIN tracker AS mt{$tracker_id} ON (mt{$tracker_id}.id = m{$tracker_id}.tracker_id AND m{$tracker_id}.tracker_id = {$tracker_id})";
    }
}
