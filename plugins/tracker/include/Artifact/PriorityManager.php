<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Exception\MustBeNonEmpty;
use ProjectManager;
use Tracker_Artifact_Exception_CannotRankWithMyself;
use Tracker_Artifact_PriorityHistoryChange;
use Tracker_Artifact_PriorityHistoryDao;
use Tracker_ArtifactFactory;
use Tuleap\DB\DBFactory;
use Tuleap\Tracker\Artifact\Dao\PriorityDao;
use UserManager;

class PriorityManager
{
    public function __construct(
        private readonly PriorityDao $priority_dao,
        private readonly Tracker_Artifact_PriorityHistoryDao $priority_history_dao,
        private readonly UserManager $user_manager,
        private readonly Tracker_ArtifactFactory $tracker_artifact_factory,
        private readonly EasyDB $db,
    ) {
    }

    public static function build(): self
    {
        return new self(
            new PriorityDao(),
            new Tracker_Artifact_PriorityHistoryDao(),
            UserManager::instance(),
            Tracker_ArtifactFactory::instance(),
            DBFactory::getMainTuleapDBConnection()->getDB(),
        );
    }

    public function startTransaction(): void
    {
        $this->db->beginTransaction();
    }

    public function commit(): void
    {
        $this->db->commit();
    }

    public function rollback(): void
    {
        $this->db->rollBack();
    }

    public function getGlobalRank(int $artifact_id): ?int
    {
        return $this->priority_dao->getGlobalRank($artifact_id);
    }

    /**
     * @throws Tracker_Artifact_Exception_CannotRankWithMyself
     */
    public function moveArtifactAfter(int $artifact_id, int $predecessor_id): void
    {
        $this->priority_dao->moveArtifactAfter($artifact_id, $predecessor_id);
    }

    /**
     * @throws Tracker_Artifact_Exception_CannotRankWithMyself
     */
    public function moveArtifactAfterWithHistoryChangeLogging(int $artifact_id, int $predecessor_id, int $context_id, int $project_id): void
    {
        $old_global_rank = $this->getGlobalRank($artifact_id);
        $this->priority_dao->moveArtifactAfter($artifact_id, $predecessor_id);
        $new_global_rank = $this->getGlobalRank($artifact_id);

        if ($old_global_rank !== $new_global_rank) {
            $this->logPriorityChange($artifact_id, $predecessor_id, $artifact_id, $context_id, $project_id, $old_global_rank);
        }
    }

    /**
     * @param list<int> $list_of_artifact_ids
     * @throws Tracker_Artifact_Exception_CannotRankWithMyself
     * @throws MustBeNonEmpty
     */
    public function moveListOfArtifactsBefore(array $list_of_artifact_ids, int $successor_id, int $context_id, int $project_id): void
    {
        $ranks_before_move = $this->getGlobalRanks($list_of_artifact_ids);

        $this->priority_dao->moveListOfArtifactsBefore($list_of_artifact_ids, $successor_id);

        $this->logPriorityChangesWhenMovingListOfArtifactsBefore($list_of_artifact_ids, $ranks_before_move, $successor_id, $context_id, $project_id);
    }

    /**
     * @param list<int> $list_of_artifact_ids
     * @param array<int, int> $ranks_before_move
     */
    private function logPriorityChangesWhenMovingListOfArtifactsBefore(
        array $list_of_artifact_ids,
        array $ranks_before_move,
        int $successor_id,
        int $context_id,
        int $project_id,
    ): void {
        for ($i = 0; $i < count($list_of_artifact_ids); $i++) {
            $artifact_id       = $list_of_artifact_ids[$i];
            $artifact_lower_id = $successor_id;

            if (isset($list_of_artifact_ids[$i + 1])) {
                $artifact_lower_id = $list_of_artifact_ids[$i + 1];
            }

            $rank_after_move = $this->getGlobalRank($artifact_id);

            if ($this->didArtifactRankChange($ranks_before_move[$artifact_id], $rank_after_move)) {
                $this->logPriorityChange($artifact_id, $artifact_id, $artifact_lower_id, $context_id, $project_id, $ranks_before_move[$artifact_id]);
            }
        }
    }

    /**
     * @param list<int> $list_of_artifact_ids
     * @throws MustBeNonEmpty
     * @throws Tracker_Artifact_Exception_CannotRankWithMyself
     */
    public function moveListOfArtifactsAfter(array $list_of_artifact_ids, int $predecessor_id, int $context_id, int $project_id): void
    {
        $ranks_before_move = $this->getGlobalRanks($list_of_artifact_ids);

        $this->priority_dao->moveListOfArtifactsAfter($list_of_artifact_ids, $predecessor_id);

        $this->logPriorityChangesWhenMovingListOfArtifactsAfter($list_of_artifact_ids, $ranks_before_move, $predecessor_id, $context_id, $project_id);
    }

    /**
     * @param list<int> $list_of_artifact_ids
     * @param array<int, int> $ranks_before_move
     */
    private function logPriorityChangesWhenMovingListOfArtifactsAfter(
        array $list_of_artifact_ids,
        array $ranks_before_move,
        int $predecessor_id,
        int $context_id,
        int $project_id,
    ): void {
        for ($i = 0; $i < count($list_of_artifact_ids); $i++) {
            $artifact_id        = $list_of_artifact_ids[$i];
            $artifact_higher_id = $predecessor_id;

            if (isset($list_of_artifact_ids[$i - 1])) {
                $artifact_higher_id = $list_of_artifact_ids[$i - 1];
            }

            $rank_after_move = $this->getGlobalRank($artifact_id);

            if ($this->didArtifactRankChange($ranks_before_move[$artifact_id], $rank_after_move)) {
                $this->logPriorityChange($artifact_id, $artifact_higher_id, $artifact_id, $context_id, $project_id, $ranks_before_move[$artifact_id]);
            }
        }
    }

    private function didArtifactRankChange(int $rank_before_move, int $rank_after_move): bool
    {
        return $rank_after_move !== $rank_before_move;
    }

    /**
     * @param list<int> $list_of_artifact_ids
     * @return array<int, int>
     * @throws MustBeNonEmpty
     */
    private function getGlobalRanks(array $list_of_artifact_ids): array
    {
        $rows  = $this->priority_dao->getGlobalRanks($list_of_artifact_ids);
        $ranks = [];
        foreach ($rows as $row) {
            $ranks[$row['artifact_id']] = $row['rank'];
        }
        return $ranks;
    }

    public function getArtifactPriorityHistory(Artifact $artifact): array
    {
        $rows                     = $this->priority_history_dao->getArtifactPriorityHistory($artifact->getId());
        $priority_history_changes = [];

        foreach ($rows as $row) {
            $priority_history_changes[] = $this->getInstanceFromRow($row);
        }

        return $priority_history_changes;
    }

    private function logPriorityChange(
        int $moved_artifact_id,
        int $artifact_higher_id,
        int $artifact_lower_id,
        int $context_id,
        int $project_id,
        int $old_global_rank,
    ): void {
        $artifact = $this->tracker_artifact_factory->getArtifactById($moved_artifact_id);

        if ($artifact) {
            $tracker = $artifact->getTracker();

            if ($tracker && $tracker->arePriorityChangesShown()) {
                $this->priority_history_dao->logPriorityChange(
                    $moved_artifact_id,
                    $artifact_higher_id,
                    $artifact_lower_id,
                    $context_id,
                    $project_id,
                    $this->user_manager->getCurrentUser()->getId(),
                    time(),
                    $old_global_rank
                );
            }
        }
    }

    public function getInstanceFromRow(array $row): Tracker_Artifact_PriorityHistoryChange
    {
        return new Tracker_Artifact_PriorityHistoryChange(
            $this->tracker_artifact_factory,
            $row['id'],
            $this->tracker_artifact_factory->getArtifactById($row['moved_artifact_id']),
            $this->tracker_artifact_factory->getArtifactById($row['artifact_id_higher']),
            $this->tracker_artifact_factory->getArtifactById($row['artifact_id_lower']),
            $row['context'],
            ProjectManager::instance()->getProject($row['project_id']),
            (bool) $row['has_been_raised'],
            $this->user_manager->getUserById($row['prioritized_by']),
            $row['prioritized_on']
        );
    }

    public function deletePriority(Artifact $artifact): bool
    {
        return $this->priority_dao->remove($artifact->getId()) &&
               $this->priority_history_dao->deletePriorityChangesHistory($artifact->getId());
    }

    public function putArtifactAtAGivenRank(Artifact $artifact, int $rank): void
    {
        $this->priority_dao->putArtifactAtAGivenRank($artifact->getId(), $rank);
    }
}
