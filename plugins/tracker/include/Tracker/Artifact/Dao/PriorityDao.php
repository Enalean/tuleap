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

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\Dao;

use Override;
use ParagonIE\EasyDB\EasyStatement;
use ParagonIE\EasyDB\Exception\MustBeNonEmpty;
use Throwable;
use Tracker_Artifact_Exception_CannotRankWithMyself;
use Tuleap\DB\DataAccessObject;

/**
 * Manage artifacts' priority in the database
 *
 * @see PriorityDaoTest for the test cases
 */
class PriorityDao extends DataAccessObject implements SearchArtifactGlobalRank
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
    public function moveArtifactAfter(int $artifact_id, int $predecessor_id): bool
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
     * @param list<int> $list_of_artifact_ids
     * @return bool true if success
     * @throws Tracker_Artifact_Exception_CannotRankWithMyself
     */
    public function moveListOfArtifactsBefore(array $list_of_artifact_ids, int $successor_id): bool
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
     * @param list<int> $list_of_artifact_ids
     * @return bool true if success
     * @throws Tracker_Artifact_Exception_CannotRankWithMyself
     */
    public function moveListOfArtifactsAfter(array $list_of_artifact_ids, int $predecessor_id): bool
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
     * @return bool true if success
     */
    public function putArtifactAtTheEndWithoutTransaction(int $artifact_id): bool
    {
        $sql = <<<SQL
        REPLACE INTO tracker_artifact_priority_rank (artifact_id, `rank`)
            SELECT ?, MAX(`rank`) + 1
            FROM tracker_artifact_priority_rank
        SQL;

        $this->getDB()->q($sql, $artifact_id);

        return true;
    }

    public function putArtifactAtAGivenRank(int $artifact_id, int $rank): void
    {
        $sql = 'REPLACE INTO tracker_artifact_priority_rank (artifact_id, `rank`) VALUES (?, ?)';

        $this->getDB()->q($sql, $artifact_id, $rank);
    }

    /**
     * Remove an item from the linked list
     */
    public function remove(int $id): bool
    {
        $this->getDB()->delete('tracker_artifact_priority_rank', ['artifact_id' => $id]);
        return true;
    }

    #[Override]
    public function getGlobalRank(int $artifact_id): ?int
    {
        $sql = 'SELECT `rank` FROM tracker_artifact_priority_rank WHERE artifact_id = ?';

        return $this->getDB()->cell($sql, $artifact_id);
    }

    /**
     * @param non-empty-list<int> $list_of_artifact_ids
     * @return list<array{
     *     artifact_id: int,
     *     rank: int,
     * }>
     * @throws MustBeNonEmpty
     */
    public function getGlobalRanks(array $list_of_artifact_ids): array
    {
        $in_statement = EasyStatement::open()->in('artifact_id IN (?*)', $list_of_artifact_ids);

        $sql = "SELECT artifact_id, `rank` FROM tracker_artifact_priority_rank WHERE $in_statement";
        return $this->getDB()->q($sql, ...$list_of_artifact_ids);
    }

    /**
     * @param list<int> $list_of_artifact_ids
     * @throws Tracker_Artifact_Exception_CannotRankWithMyself
     */
    private function moveListOfArtifacts(array $list_of_artifact_ids, int $reference_id, int $offset): bool
    {
        $list_of_artifact_ids = array_unique(array_filter($list_of_artifact_ids));
        if ($list_of_artifact_ids === []) {
            return false;
        }

        if (in_array($reference_id, $list_of_artifact_ids)) {
            throw new Tracker_Artifact_Exception_CannotRankWithMyself($reference_id);
        }

        try {
            $this->getDBTransactionExecutor()->execute(function () use ($reference_id, $offset, $list_of_artifact_ids) {
                $rank  = $this->getGlobalRank($reference_id) + $offset;
                $count = count($list_of_artifact_ids);

                $sql = 'UPDATE tracker_artifact_priority_rank
                        SET `rank` = `rank` + ?
                        WHERE `rank` >= ?';
                $this->getDB()->q($sql, $count, $rank);

                $new_ranks = [];
                $params    = [];
                foreach (array_values($list_of_artifact_ids) as $position => $id) {
                    $new_ranks[] = 'WHEN artifact_id = ? THEN ?';
                    $params[]    = $id;
                    $params[]    = ((int) $rank) + $position;
                }
                $in_statement = EasyStatement::open()->in('artifact_id IN(?*)', $list_of_artifact_ids);

                $sql = 'UPDATE tracker_artifact_priority_rank
                        SET `rank` = CASE ' . implode(' ', $new_ranks) . " ELSE `rank` END
                        WHERE $in_statement";
                $this->getDB()->q($sql, ...$params, ...$list_of_artifact_ids);
            });
            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
