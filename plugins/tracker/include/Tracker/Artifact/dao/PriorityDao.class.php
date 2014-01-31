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

/**
 * Manage artifacts priority in database
 *
 * @see PriorityDao.phpt for the test cases
 */
class Tracker_Artifact_PriorityDao extends DataAccessObject {

    /**
     * Move an artifact before another one
     *
     * A -> B -> C -> D
     *
     * moveArtifactBefore(A, D) =>
     * B -> C -> A -> D
     *
     * @see PriorityDao.phpt for the test cases
     *
     * @return bool true if success
     */
    public function moveArtifactBefore($artifact_id, $successor_id) {
        if ($artifact_id == $successor_id) {
            throw new Tracker_Artifact_Exception_CannotRankWithMyself($artifact_id);
        }
        $this->da->startTransaction();
        $predecessor_id = $this->searchPredecessor($successor_id);
        if ($predecessor_id !== false && $predecessor_id != $artifact_id && $this->removeAndInsert($predecessor_id, $artifact_id)) {
            $this->da->commit();
            return true;
        }
        $this->da->rollback();
        return false;
    }

    /**
     * Move an artifact after another one
     *
     * A -> B -> C -> D
     *
     * moveArtifactAfter(A, D) =>
     * B -> C -> D -> A
     *
     * @see PriorityDao.phpt for the test cases
     *
     * @return bool true if success
     */
    public function moveArtifactAfter($artifact_id, $predecessor_id) {
        if ($artifact_id == $predecessor_id) {
            throw new Tracker_Artifact_Exception_CannotRankWithMyself($artifact_id);
        }
        $this->da->startTransaction();
        if ($this->removeAndInsert($predecessor_id, $artifact_id)) {
            $this->da->commit();
            return true;
        }
        $this->da->rollback();
        return false;
    }

    /**
     * Put an artifact at the end
     *
     * A -> B -> C -> D
     *
     * putArtifactAtTheEnd(E) =>
     * A -> B -> C -> D -> E
     *
     * @see PriorityDao.phpt for the test cases
     *
     * @todo: check that the artifact doesn't already exist in the list
     *
     * @return bool true if success
     */
    public function putArtifactAtTheEnd($artifact_id) {
        $this->da->startTransaction();
        $predecessor_id = $this->searchPredecessor(null);
        if ($predecessor_id !== false && $this->insert($predecessor_id, $artifact_id)) {
            $this->da->commit();
            return true;
        }
        $this->da->rollback();
        return false;
    }

    private function removeAndInsert($predecessor_id, $artifact_id) {
        return $this->remove($artifact_id) && $this->insert($predecessor_id, $artifact_id);
    }

    private function searchPredecessor($id) {
        $equals_id = $id === null ? 'IS NULL' : '= '. $this->da->escapeInt($id);
        $sql = "SELECT curr_id
                FROM tracker_artifact_priority
                WHERE succ_id $equals_id";
        $result = $this->retrieve($sql);
        if ($result) {
            $row = $result->getRow();
            return $row['curr_id'];
        }
        return false;
    }

    /**
     * Remove an item from the linked list
     */
    public function remove($id) {
        $id = $this->da->escapeInt($id);

        // Change the successor pointer of the actual parent
        $sql = "UPDATE tracker_artifact_priority AS previous_parent
                        INNER JOIN tracker_artifact_priority AS item_to_remove
                                ON (previous_parent.succ_id = item_to_remove.curr_id AND item_to_remove.curr_id = $id)
                SET previous_parent.succ_id = item_to_remove.succ_id";
        $result = $this->update($sql);
        if (!$result) return false;

        // Reorder things
        $sql = "UPDATE tracker_artifact_priority AS next_sibling
                        INNER JOIN tracker_artifact_priority AS item_to_remove
                                ON (next_sibling.rank > item_to_remove.rank AND item_to_remove.curr_id = $id)
                SET next_sibling.rank = next_sibling.rank - 1";
        $result = $this->update($sql);
        if (!$result) return false;

        // Remove the item
        $sql = "DELETE FROM tracker_artifact_priority WHERE curr_id = $id";
        return $this->update($sql);
    }

    /**
     * After $predecessor_id, insert $id
     */
    private function insert($predecessor_id, $id) {
        $id = $this->da->escapeInt($id);
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
        $result = $this->update($sql);
        if (!$result) return false;

        // Reorder things
        $sql = "UPDATE tracker_artifact_priority AS next_sibling
                        INNER JOIN tracker_artifact_priority AS new_item
                                ON (next_sibling.rank >= new_item.rank
                                    AND next_sibling.curr_id $differs_from_predecessor_id
                                    AND new_item.curr_id = $id)
                SET next_sibling.rank = next_sibling.rank + 1";
        $result = $this->update($sql);
        if (!$result) return false;

        // Fix successor pointer of the predecessor
        $sql = "UPDATE tracker_artifact_priority AS new_parent
                SET new_parent.succ_id = $id
                WHERE new_parent.curr_id $equals_predecessor_id";
        return $this->update($sql);
    }
}
?>
