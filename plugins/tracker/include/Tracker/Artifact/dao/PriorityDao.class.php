<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

/**
 * Manage artifacts priority in database
 *
 * @see PriorityDaoTest for the test cases
 */
class Tracker_Artifact_PriorityDao extends DataAccessObject
{
    /**
     * Move an artifact after another one
     *
     * A -> B -> C -> D
     *
     * moveArtifactAfter(A, D) =>
     * B -> C -> D -> A
     *
     * @return bool true if success
     * @throws Tracker_Artifact_Exception_CannotRankWithMyself
     */
    public function moveArtifactAfter($artifact_id, $predecessor_id)
    {
        return $this->moveListOfArtifactsAfter([$artifact_id], $predecessor_id);
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
     * @throws Tracker_Artifact_Exception_CannotRankWithMyself
     */
    public function moveListOfArtifactsBefore(array $list_of_artifact_ids, $successor_id)
    {
        return $this->moveListOfArtifacts($list_of_artifact_ids, $successor_id, 0);
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
     * @throws Tracker_Artifact_Exception_CannotRankWithMyself
     */
    public function moveListOfArtifactsAfter(array $list_of_artifact_ids, $predecessor_id)
    {
        return $this->moveListOfArtifacts($list_of_artifact_ids, $predecessor_id, 1);
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
    public function putArtifactAtTheEndWithoutTransaction($artifact_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);

        $sql = "REPLACE INTO tracker_artifact_priority_rank (artifact_id, `rank`)
                SELECT $artifact_id, MAX(`rank`) + 1
                FROM tracker_artifact_priority_rank";

        return $this->update($sql);
    }

    public function putArtifactAtAGivenRank($artifact_id, $rank)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $rank        = $this->da->escapeInt($rank);

        $sql = "REPLACE INTO tracker_artifact_priority_rank (artifact_id, `rank`)
                VALUES ($artifact_id, $rank)";

        $this->update($sql);
    }

    /**
     * Remove an item from the linked list
     */
    public function remove($id)
    {
        $id = $this->da->escapeInt($id);

        $sql = "DELETE FROM tracker_artifact_priority_rank WHERE artifact_id = $id";

        return $this->update($sql);
    }

    public function getGlobalRank($artifact_id): ?int
    {
        $artifact_id = $this->da->escapeInt($artifact_id);

        $sql = "SELECT `rank` FROM tracker_artifact_priority_rank
                WHERE artifact_id = $artifact_id";

        $result = $this->retrieve($sql);
        if (! $result) {
            return null;
        }

        $row = $result->getRow();
        return (int) $row['rank'];
    }

    public function getGlobalRanks(array $list_of_artifact_ids)
    {
        $list_of_artifact_ids = $this->da->escapeIntImplode($list_of_artifact_ids);
        $sql                  = "SELECT * FROM tracker_artifact_priority_rank
                WHERE artifact_id IN ($list_of_artifact_ids)";
        return $this->retrieve($sql);
    }

    /**
     * For debugging purpose only
     */
    public function debug()
    {
        $res = $this->retrieve("SELECT * FROM tracker_artifact_priority_rank ORDER BY `rank`");
        printf("%10s|%10s|\n", 'artifact_id', 'rank');
        printf("----------+----------+----------+\n");
        foreach ($res as $row) {
            printf("%10s|%10s|\n", $row['artifact_id'], $row['rank']);
        }
        echo "\n\n";
    }

    private function moveListOfArtifacts(array $list_of_artifact_ids, $reference_id, $offset)
    {
        $list_of_artifact_ids = array_unique(array_filter($list_of_artifact_ids));
        if (empty($list_of_artifact_ids)) {
            return false;
        }

        if (in_array($reference_id, $list_of_artifact_ids)) {
            throw new Tracker_Artifact_Exception_CannotRankWithMyself($reference_id);
        }

        try {
            $transaction_executor = new \Tuleap\DB\DBTransactionExecutorWithConnection(\Tuleap\DB\DBFactory::getMainTuleapDBConnection());
            $transaction_executor->execute(function () use ($reference_id, $offset, $list_of_artifact_ids) {
                $rank  = $this->da->escapeInt($this->getGlobalRank($reference_id) + $offset);
                $count = $this->da->escapeInt(count($list_of_artifact_ids));

                $sql = "UPDATE tracker_artifact_priority_rank
                        SET `rank` = `rank` + $count
                        WHERE `rank` >= $rank";
                if (! $this->update($sql)) {
                    throw new RuntimeException('Cannot update rank');
                }

                $new_ranks = [];
                foreach (array_values($list_of_artifact_ids) as $position => $id) {
                    $id       = $this->da->escapeInt($id);
                    $new_rank = $this->da->escapeInt(((int) $rank) + $position);

                    $new_ranks[] = "WHEN artifact_id = $id THEN $new_rank";
                }
                $ids = $this->da->escapeIntImplode($list_of_artifact_ids);

                $sql = "UPDATE tracker_artifact_priority_rank
                        SET `rank` = CASE " . implode(' ', $new_ranks) . " ELSE `rank` END
                        WHERE artifact_id IN ($ids)";
                if (! $this->update($sql)) {
                    throw new RuntimeException('Cannot update rank');
                }
            });
            return true;
        } catch (Throwable $exception) {
            return false;
        }
    }
}
