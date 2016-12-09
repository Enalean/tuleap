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
     * @return bool true if success
     */
    public function moveArtifactBefore($artifact_id, $successor_id) {
        if ($artifact_id == $successor_id) {
            throw new Tracker_Artifact_Exception_CannotRankWithMyself($artifact_id);
        }
        $this->da->startTransaction();
        try {
            $predecessor_id = $this->searchPredecessor($successor_id);
            if ($predecessor_id != $artifact_id && $this->removeAndInsert($predecessor_id, $artifact_id)) {
                $this->da->commit();
                return true;
            }
        } catch (Tracker_Artifact_Dao_NoPredecessorException $exception) {
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
     * Move a set of artifacts before another one
     *
     * A -> B -> C -> D
     *
     * moveListOfArtifactsBefore([A, C], D) =>
     * B -> A -> C -> D
     *
     * @return bool true if success
     */
    public function moveListOfArtifactsBefore(array $list_of_artifact_ids, $successor_id) {
        $list_of_artifact_ids = array_unique(array_filter($list_of_artifact_ids));
        if (in_array($successor_id, $list_of_artifact_ids)) {
            throw new Tracker_Artifact_Exception_CannotRankWithMyself($successor_id);
        }
        try {
            $this->da->startTransaction();

            $this->removeArtifactsWithoutUpdatingRank($list_of_artifact_ids);

            // Moving [...] before A is equivalent to moving [...] after the predecessor of A
            $new_predecessor_id = $this->searchPredecessor($successor_id);

            $this->insertArtifactsAfter($list_of_artifact_ids, $new_predecessor_id);

            $this->da->commit();
            return true;
        } catch (Tracker_Artifact_Dao_NoPredecessorException $exception) {
            $this->da->rollback();
            return false;
        }
    }

    /**
     * Move a set of artifacts before another one
     *
     * A -> B -> C -> D
     *
     * moveListOfArtifactsAfter([A, C], D) =>
     * B -> D -> A -> C
     *
     * @return bool true if success
     */
    public function moveListOfArtifactsAfter(array $list_of_artifact_ids, $predecessor_id) {
        $list_of_artifact_ids = array_unique(array_filter($list_of_artifact_ids));
        if (! $list_of_artifact_ids) {
            return true;
        }
        if (in_array($predecessor_id, $list_of_artifact_ids)) {
            throw new Tracker_Artifact_Exception_CannotRankWithMyself($predecessor_id);
        }
        try {
            $this->da->startTransaction();

            $this->removeArtifactsWithoutUpdatingRank($list_of_artifact_ids);
            $this->insertArtifactsAfter($list_of_artifact_ids, $predecessor_id);

            $this->da->commit();
            return true;
        } catch (Tracker_Artifact_Dao_NoPredecessorException $exception) {
            $this->da->rollback();
            return false;
        }
    }

    private function updateSuccessorRank(array $list_of_artifact_ids, $predecessor_id) {
        $old_successor_rank = $this->searchSuccessorRank($predecessor_id);
        $nb                 = count($list_of_artifact_ids);
        $sql                = "UPDATE tracker_artifact_priority SET rank = rank + $nb WHERE rank > $old_successor_rank";
        $this->update($sql);
    }

    private function insertArtifactsAfter(
        array $list_of_artifact_ids,
        $predecessor_id
    ) {
        $id = $this->da->escapeInt($predecessor_id);
        $this->updateSuccessorRank($list_of_artifact_ids, $predecessor_id);
        list($new_entries, $new_successor_id) = $this->prepareNewEntriesAndSuccessorId($list_of_artifact_ids, $predecessor_id);
        $this->insertItemsAtTheRightPosition($new_entries);
        $this->updateSuccessor($id, $new_successor_id);
    }

    private function prepareNewEntriesAndSuccessorId(
        array $list_of_artifact_ids,
        $predecessor_id
    ) {
        list($current_successor_id, $current_rank) = $this->getCurrentRankAndSuccessor($predecessor_id);
        $new_entries      = array();
        $new_successor_id = null;
        foreach ($list_of_artifact_ids as $artifact_id) {
            if ($new_successor_id === null) {
                $new_successor_id = $artifact_id;
            } else {
                $new_entries[] = "($predecessor_id, $artifact_id, $current_rank)";
            }
            $current_rank++;
            $predecessor_id = $artifact_id;
        }
        $new_entries[] = "($predecessor_id, $current_successor_id, $current_rank)";

        return array($new_entries, $new_successor_id);
    }

    private function insertItemsAtTheRightPosition($new_entries) {
        $new_entries_imploded = implode(', ', $new_entries);
        $sql = "INSERT INTO tracker_artifact_priority (curr_id, succ_id, rank) VALUES $new_entries_imploded";
        $this->update($sql);
    }

    private function updateSuccessor($curr_id, $new_successor_id) {
        $equals_id = "IS NULL";
        if ($curr_id) {
            $equals_id = "= $curr_id";
        }
        $new_successor_id = $this->da->escapeInt($new_successor_id);
        $sql = "UPDATE tracker_artifact_priority
                SET succ_id = $new_successor_id
                WHERE curr_id $equals_id
                  AND IFNULL(succ_id, 0) <> $new_successor_id";
        $this->update($sql);
    }

    private function getCurrentRankAndSuccessor($id) {
        $equals_id = "IS NULL";
        $id = $this->da->escapeInt($id);
        if ($id) {
            $equals_id = "= $id";
        }
        $sql = "SELECT succ_id, rank FROM tracker_artifact_priority WHERE curr_id $equals_id";
        $result = $this->retrieve($sql);
        $row = $result->getRow();
        $current_successor_id = $row['succ_id'] === null ? 'NULL' : $row['succ_id'];
        $current_rank = $row['rank'];

        return array($current_successor_id, $current_rank);
    }

    private function removeArtifactsWithoutUpdatingRank(array $list_of_artifact_ids) {
        $this->updateSuccessorPointerOfCurrentParents($list_of_artifact_ids);

        // Remove all items
        $artifact_ids_imploded = $this->da->escapeIntImplode($list_of_artifact_ids);
        $sql = "DELETE FROM tracker_artifact_priority WHERE curr_id IN ($artifact_ids_imploded)";
        $this->update($sql);
    }

    private function updateSuccessorPointerOfCurrentParents(array $list_of_artifact_ids) {
        $replacements = $this->extractNewSuccessors($list_of_artifact_ids);
        if (! $replacements) {
            return;
        }

        $whens = '';
        foreach ($replacements as $old_value => $new_value) {
            if (! $new_value) {
                $new_value = 'NULL';
            }
            if (! $old_value) {
                $old_value = 'NULL';
            }
            $whens .= " WHEN succ_id = $old_value THEN $new_value ";
        }

        $sql = "UPDATE tracker_artifact_priority
                SET succ_id = CASE $whens ELSE succ_id END";
        $this->update($sql);
    }

    private function extractNewSuccessors($list_of_artifact_ids) {
        $artifact_ids_imploded = $this->da->escapeIntImplode($list_of_artifact_ids);
        $sql = "SELECT *
                FROM tracker_artifact_priority
                WHERE curr_id IN ($artifact_ids_imploded)
                ORDER BY rank";
        $result = $this->retrieve($sql);
        $replacements = array();
        foreach ($result as $row) {
            $key = array_search($row['curr_id'], $replacements);
            if ($key === false) {
                $key = $row['curr_id'];
            }
            $replacements[$key] = $row['succ_id'];
        }

        return $replacements;
    }

    /**
     * For debugging purpose only
     */
    public function debug() {
        $res = $this->retrieve("SELECT * FROM tracker_artifact_priority ORDER BY rank");
        printf("%10s|%10s|%10s|\n", 'curr_id', 'succ_id', 'rank');
        printf("----------+----------+----------+\n");
        foreach ($res as $row) {
            printf("%10s|%10s|%10s|\n", $row['curr_id'], $row['succ_id'], $row['rank']);
        }
        echo "\n\n";
    }

    /**
     * Put an artifact at the end
     *
     * A -> B -> C -> D
     *
     * putArtifactAtTheEndWithoutTransaction(E) =>
     * A -> B -> C -> D -> E
     *
     * @todo: check that the artifact doesn't already exist in the list
     *
     * @return bool true if success
     */
    public function putArtifactAtTheEndWithoutTransaction($artifact_id) {
        try {
            $predecessor_id = $this->searchPredecessor(null);
            $artifact_id    = $this->da->escapeInt($artifact_id);

            if ($predecessor_id === null) {
                $select_predecessor = 'curr_id IS NULL';
            } else {
                $select_predecessor = 'curr_id = '. $this->da->escapeInt($predecessor_id);
            }
            $sql = "INSERT INTO tracker_artifact_priority(curr_id, succ_id, rank)
                    SELECT $artifact_id, null, rank + 1
                    FROM tracker_artifact_priority
                    WHERE $select_predecessor";
            if (! $this->update($sql)) {
                return false;
            }

            $sql = "UPDATE tracker_artifact_priority
                    SET succ_id = $artifact_id
                    WHERE $select_predecessor";
            if ($this->update($sql)) {
                return true;
            }
        } catch (Tracker_Artifact_Dao_NoPredecessorException $exception) {
        }
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
        if (! $result && ! $result->count()) {
            throw new Tracker_Artifact_Dao_NoPredecessorException();
        }

        $row = $result->getRow();
        return $row['curr_id'];
    }

    private function searchSuccessorRank($id) {
        $equals_id = $id === null ? 'IS NULL' : '= '. $this->da->escapeInt($id);
        $sql = "SELECT rank
                FROM tracker_artifact_priority
                WHERE curr_id $equals_id";
        $result = $this->retrieve($sql);
        if (! $result && ! $result->count()) {
            throw new Tracker_Artifact_Dao_NoPredecessorException();
        }

        $row = $result->getRow();
        return $row['rank'];
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

    /**
    * Get Global rank of the artifact
    * @return int global rank of the artifact, null otherwise
    */
    public function getGlobalRank($artifact_id) {
        $artifact_id = $this->da->escapeInt($artifact_id);

        $sql = "SELECT rank FROM tracker_artifact_priority
                WHERE curr_id = $artifact_id";

        $result = $this->retrieve($sql);
        if (!$result) {
            return;
        }

        $row = $result->getRow();
        return $row['rank'];
    }

    public function getGlobalRanks(array $list_of_artifact_ids) {
        $list_of_artifact_ids = $this->da->escapeIntImplode($list_of_artifact_ids);
        $sql = "SELECT * FROM tracker_artifact_priority
                WHERE curr_id IN ($list_of_artifact_ids)";
        return $this->retrieve($sql);
    }
}
