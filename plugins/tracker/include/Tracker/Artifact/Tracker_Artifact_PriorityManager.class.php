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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_Artifact_PriorityManager
{
    /**
     * @var Tracker_Artifact_PriorityDao
     */
    private $priority_dao;

    /**
     * @var Tracker_Artifact_PriorityHistoryDao
     */
    private $priority_history_dao;

    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $tracker_artifact_factory;


    public function __construct(
        Tracker_Artifact_PriorityDao $priority_dao,
        Tracker_Artifact_PriorityHistoryDao $priority_history_dao,
        UserManager $user_manager,
        Tracker_ArtifactFactory $tracker_artifact_factory
    ) {
        $this->priority_dao             = $priority_dao;
        $this->priority_history_dao     = $priority_history_dao;
        $this->user_manager             = $user_manager;
        $this->tracker_artifact_factory = $tracker_artifact_factory;
    }

    public static function build(): self
    {
        return new self(
            new Tracker_Artifact_PriorityDao(),
            new Tracker_Artifact_PriorityHistoryDao(),
            UserManager::instance(),
            Tracker_ArtifactFactory::instance()
        );
    }

    public function enableExceptionsOnError()
    {
        $this->priority_dao->enableExceptionsOnError();
    }

    public function startTransaction()
    {
        $this->priority_dao->startTransaction();
    }

    public function commit()
    {
        $this->priority_dao->commit();
    }

    public function rollback()
    {
        $this->priority_dao->rollBack();
    }

    public function remove($artifact_id)
    {
        return $this->priority_dao->remove($artifact_id);
    }

    public function getGlobalRank($artifact_id)
    {
        return $this->priority_dao->getGlobalRank($artifact_id);
    }

    public function moveArtifactAfter($artifact_id, $predecessor_id)
    {
        $this->priority_dao->moveArtifactAfter($artifact_id, $predecessor_id);
    }

    public function moveArtifactAfterWithHistoryChangeLogging($artifact_id, $predecessor_id, $context_id, $project_id)
    {
        $old_global_rank = $this->getGlobalRank($artifact_id);
        $this->priority_dao->moveArtifactAfter($artifact_id, $predecessor_id);
        $new_global_rank = $this->getGlobalRank($artifact_id);

        if ($old_global_rank !== $new_global_rank) {
            $this->logPriorityChange($artifact_id, $predecessor_id, $artifact_id, $context_id, $project_id, $old_global_rank);
        }
    }

    /**
     * @throws Tracker_Artifact_Exception_CannotRankWithMyself
     */
    public function moveListOfArtifactsBefore(array $list_of_artifact_ids, $successor_id, $context_id, $project_id)
    {
        $ranks_before_move = $this->getGlobalRanks($list_of_artifact_ids);

        $this->priority_dao->moveListOfArtifactsBefore($list_of_artifact_ids, $successor_id);

        $this->logPriorityChangesWhenMovingListOfArtifactsBefore($list_of_artifact_ids, $ranks_before_move, $successor_id, $context_id, $project_id);
    }

    private function logPriorityChangesWhenMovingListOfArtifactsBefore(array $list_of_artifact_ids, array $ranks_before_move, $successor_id, $context_id, $project_id)
    {
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

    public function moveListOfArtifactsAfter(array $list_of_artifact_ids, $predecessor_id, $context_id, $project_id)
    {
        $ranks_before_move = $this->getGlobalRanks($list_of_artifact_ids);

        $this->priority_dao->moveListOfArtifactsAfter($list_of_artifact_ids, $predecessor_id);

        $this->logPriorityChangesWhenMovingListOfArtifactsAfter($list_of_artifact_ids, $ranks_before_move, $predecessor_id, $context_id, $project_id);
    }

    private function logPriorityChangesWhenMovingListOfArtifactsAfter(array $list_of_artifact_ids, array $ranks_before_move, $predecessor_id, $context_id, $project_id)
    {
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

    private function didArtifactRankChange($rank_before_move, $rank_after_move)
    {
        return $rank_after_move !== $rank_before_move;
    }

    private function getGlobalRanks($list_of_artifact_ids)
    {
        $rows  = $this->priority_dao->getGlobalRanks($list_of_artifact_ids);
        $ranks = array();
        foreach ($rows as $row) {
            $ranks[$row['artifact_id']] = $row['rank'];
        }
        return $ranks;
    }

    public function getArtifactPriorityHistory(Tracker_Artifact $artifact)
    {
        $rows                     = $this->priority_history_dao->getArtifactPriorityHistory($artifact->getId());
        $priority_history_changes = array();

        foreach ($rows as $row) {
            $priority_history_changes[] = $this->getInstanceFromRow($row);
        }

        return $priority_history_changes;
    }

    private function logPriorityChange($moved_artifact_id, $artifact_higher_id, $artifact_lower_id, $context_id, $project_id, $old_global_rank)
    {
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

    /**
     * @return Tracker_Artifact_PriorityHistoryChange
     */
    public function getInstanceFromRow($row)
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

    public function deletePriority(Tracker_Artifact $artifact)
    {
        return $this->priority_dao->remove($artifact->getId()) &&
        $this->priority_history_dao->deletePriorityChangesHistory($artifact->getId());
    }

    public function putArtifactAtAGivenRank(Tracker_Artifact $artifact, $rank)
    {
        $this->priority_dao->putArtifactAtAGivenRank($artifact->getId(), $rank);
    }
}
