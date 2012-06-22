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
        $artifact_id  = $this->da->escapeInt($artifact_id);
        $successor_id = $this->da->escapeInt($successor_id);
        //TODO: Transaction?
        $sql = "SELECT rank FROM tracker_artifact_priority WHERE curr_id = $artifact_id";
        $row = $this->retrieve($sql)->getRow();

        if ($row) {
            $sql = "UPDATE tracker_artifact_priority AS new_parent,
                        tracker_artifact_priority AS item_to_move
                        INNER JOIN tracker_artifact_priority AS previous_parent
                                ON (previous_parent.succ_id = item_to_move.curr_id)

                    SET previous_parent.succ_id = item_to_move.succ_id,
                        item_to_move.succ_id    = new_parent.succ_id,
                        item_to_move.rank       = new_parent.rank + IF(item_to_move.rank < new_parent.rank, 0, 1),
                        new_parent.succ_id      = item_to_move.curr_id

                    WHERE new_parent.succ_id   = $successor_id
                      AND item_to_move.curr_id = $artifact_id";
            if ($this->update($sql)) {
                $previous_rank = $row['rank'];
                $sql = "UPDATE tracker_artifact_priority AS intermediate
                            INNER JOIN tracker_artifact_priority AS new_position
                                    ON (new_position.curr_id = $artifact_id AND intermediate.curr_id <> $artifact_id)
                        SET intermediate.rank = intermediate.rank + IF($previous_rank < new_position.rank, -1, 1)
                        WHERE ($previous_rank <= intermediate.rank AND intermediate.rank <= new_position.rank)
                           OR (new_position.rank <= intermediate.rank AND intermediate.rank <= $previous_rank)";
                return $this->update($sql);
            }
        }
    }

    public function artifactHasALesserPriorityThan($artifact_id, $predecessor_id) {
        //TODO: Transaction?
        $this->remove($artifact_id);
        $this->insert($predecessor_id, $artifact_id);
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
        $predecessor_id = $this->da->escapeInt($predecessor_id);

        // insert the new element
        $sql = "INSERT INTO tracker_artifact_priority (curr_id, succ_id, rank)
                SELECT $id, new_parent.succ_id, new_parent.rank
                FROM tracker_artifact_priority AS new_parent
                WHERE new_parent.curr_id = $predecessor_id";
        $this->update($sql);

        // Reorder things
        $sql = "UPDATE tracker_artifact_priority AS next_sibling
                        INNER JOIN tracker_artifact_priority AS new_item
                                ON (next_sibling.rank >= new_item.rank
                                    AND next_sibling.curr_id <> $predecessor_id
                                    AND new_item.curr_id = $id)
                SET next_sibling.rank = next_sibling.rank + 1";
        $this->update($sql);

        // Fix successor pointer of the predecessor
        $sql = "UPDATE tracker_artifact_priority AS new_parent
                SET new_parent.succ_id = $id
                WHERE new_parent.curr_id = $predecessor_id";
        $this->update($sql);
    }

    public function artifactHasTheLeastPriority($artifact_id) {
        $artifact_id  = $this->da->escapeInt($artifact_id);
        //TODO: Transaction?
        $sql = "SELECT curr_id, rank FROM tracker_artifact_priority WHERE succ_id IS NULL";
        $row = $this->retrieve($sql)->getRow();

        if ($row) {
            $rank        = $row['rank'] + 1;
            $ancestor_id = $row['curr_id'];
            $sql = "INSERT INTO tracker_artifact_priority(curr_id, succ_id, rank)
                    VALUES ($artifact_id, NULL, $rank)";
            if ($this->update($sql)) {

                $sql = "UPDATE tracker_artifact_priority
                        SET succ_id = $artifact_id
                        WHERE curr_id = $ancestor_id";
                return $this->update($sql);
            }
        }
    }
}
?>
