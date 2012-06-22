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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'common/dao/include/DataAccessObject.class.php';

class Tracker_Artifact_PriorityDao extends DataAccessObject {

    public function artifactHasAHigherPriorityThan($artifact_id, $successor_id) {
        //TODO: Transaction?
        $predecessor_id = $this->serchPredecessor($successor_id);
        $this->remove($artifact_id);
        $this->insert($predecessor_id, $artifact_id);
    }

    public function artifactHasALesserPriorityThan($artifact_id, $predecessor_id) {
        //TODO: Transaction?
        $this->remove($artifact_id);
        $this->insert($predecessor_id, $artifact_id);
    }

    public function artifactHasTheLeastPriority($artifact_id) {
        $predecessor_id = $this->serchPredecessor(null);
        $this->insert($predecessor_id, $artifact_id);
    }

    private function serchPredecessor($id) {
        $equals_id = $id === null ? 'IS NULL' : '= '. $this->da->escapeInt($id);
        $sql = "SELECT curr_id
                FROM tracker_artifact_priority
                WHERE succ_id $equals_id";
        $row = $this->retrieve($sql)->getRow();
        return $row['curr_id'];
    }

    /**
     * Remove an item from the linked list
     */
    private function remove($id) {
        $id = $this->da->escapeInt($id);

        // Change the successor pointer of the actual parent
        $sql = "UPDATE tracker_artifact_priority AS previous_parent
                        INNER JOIN tracker_artifact_priority AS item_to_remove
                                ON (previous_parent.succ_id = item_to_remove.curr_id AND item_to_remove.curr_id = $id)
                SET previous_parent.succ_id = item_to_remove.succ_id";
        $this->update($sql);

        // Reorder things
        $sql = "UPDATE tracker_artifact_priority AS next_sibling
                        INNER JOIN tracker_artifact_priority AS item_to_remove
                                ON (next_sibling.rank > item_to_remove.rank AND item_to_remove.curr_id = $id)
                SET next_sibling.rank = next_sibling.rank - 1";
        $this->update($sql);

        // Remove the item
        $sql = "DELETE FROM tracker_artifact_priority WHERE curr_id = $id";
        $this->update($sql);
    }

    private function insert($predecessor_id, $id) {
        $id             = $this->da->escapeInt($id);
        if ($predecessor_id === null) {
            $equals_predecessor_id       = 'IS NULL';
            $differs_from_predecessor_id = 'IS NOT NULL';
        } else {
            $predecessor_id              = $this->da->escapeInt($predecessor_id);
            $equals_predecessor_id       = '= '. $predecessor_id;
            $differs_from_predecessor_id = '<> '. $predecessor_id;
        }

        // insert the new element
        $sql = "INSERT INTO tracker_artifact_priority (curr_id, succ_id, rank)
                SELECT $id, new_parent.succ_id, new_parent.rank
                FROM tracker_artifact_priority AS new_parent
                WHERE new_parent.curr_id $equals_predecessor_id";
        $this->update($sql);

        // Reorder things
        $sql = "UPDATE tracker_artifact_priority AS next_sibling
                        INNER JOIN tracker_artifact_priority AS new_item
                                ON (next_sibling.rank >= new_item.rank
                                    AND next_sibling.curr_id $differs_from_predecessor_id
                                    AND new_item.curr_id = $id)
                SET next_sibling.rank = next_sibling.rank + 1";
        $this->update($sql);

        // Fix successor pointer of the predecessor
        $sql = "UPDATE tracker_artifact_priority AS new_parent
                SET new_parent.succ_id = $id
                WHERE new_parent.curr_id $equals_predecessor_id";
        $this->update($sql);
    }
}
?>
