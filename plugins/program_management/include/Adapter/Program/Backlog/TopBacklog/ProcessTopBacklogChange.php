<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog;

use Tracker_NoArtifactLinkFieldException;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Content\FeatureRemovalProcessor;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PrioritizeFeaturesPermissionVerifier;
use Tuleap\ProgramManagement\Program\Backlog\Feature\Content\Links\VerifyLinkedUserStoryIsNotPlanned;
use Tuleap\ProgramManagement\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Program\Backlog\Feature\VerifyIsVisibleFeature;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Content\FeatureRemoval;
use Tuleap\ProgramManagement\Program\Backlog\Rank\OrderFeatureRank;
use Tuleap\ProgramManagement\Program\Backlog\TopBacklog\CannotManipulateTopBacklog;
use Tuleap\ProgramManagement\Program\Backlog\TopBacklog\FeatureHasPlannedUserStoryException;
use Tuleap\ProgramManagement\Program\Backlog\TopBacklog\TopBacklogChange;
use Tuleap\ProgramManagement\Program\Backlog\TopBacklog\TopBacklogChangeProcessor;
use Tuleap\ProgramManagement\Program\Program;

final class ProcessTopBacklogChange implements TopBacklogChangeProcessor
{
    /**
     * @var ArtifactsExplicitTopBacklogDAO
     */
    private $explicit_top_backlog_dao;
    /**
     * @var PrioritizeFeaturesPermissionVerifier
     */
    private $prioritize_features_permission_verifier;
    /**
     * @var DBTransactionExecutor
     */
    private $db_transaction_executor;
    /**
     * @var OrderFeatureRank
     */
    private $features_rank_orderer;
    /**
     * @var VerifyLinkedUserStoryIsNotPlanned
     */
    private $story_verifier;
    /**
     * @var VerifyIsVisibleFeature
     */
    private $visible_feature_verifier;
    /**
     * @var FeatureRemovalProcessor
     */
    private $feature_removal_processor;

    public function __construct(
        PrioritizeFeaturesPermissionVerifier $prioritize_features_permission_verifier,
        ArtifactsExplicitTopBacklogDAO $explicit_top_backlog_dao,
        DBTransactionExecutor $db_transaction_executor,
        OrderFeatureRank $features_rank_orderer,
        VerifyLinkedUserStoryIsNotPlanned $story_verifier,
        VerifyIsVisibleFeature $visible_feature_verifier,
        FeatureRemovalProcessor $feature_removal_processor
    ) {
        $this->prioritize_features_permission_verifier = $prioritize_features_permission_verifier;
        $this->explicit_top_backlog_dao                = $explicit_top_backlog_dao;
        $this->db_transaction_executor                 = $db_transaction_executor;
        $this->features_rank_orderer                   = $features_rank_orderer;
        $this->story_verifier                          = $story_verifier;
        $this->visible_feature_verifier                = $visible_feature_verifier;
        $this->feature_removal_processor               = $feature_removal_processor;
    }

    /**
     * @throws CannotManipulateTopBacklog
     * @throws Tracker_NoArtifactLinkFieldException
     * @throws FeatureHasPlannedUserStoryException
     */
    public function processTopBacklogChangeForAProgram(
        Program $program,
        TopBacklogChange $top_backlog_change,
        \PFUser $user
    ): void {
        $this->db_transaction_executor->execute(function () use ($program, $top_backlog_change, $user) {
            if (! $this->prioritize_features_permission_verifier->canUserPrioritizeFeatures($program, $user)) {
                throw new CannotManipulateTopBacklog($program, $user);
            }

            $feature_add_removals = $this->filterFeaturesThatCanBeManipulated(
                $top_backlog_change->potential_features_id_to_add,
                $user,
                $program
            );

            if (count($feature_add_removals) > 0) {
                if ($top_backlog_change->remove_program_increments_link_to_feature_to_add) {
                    $this->removeFeaturesFromProgramIncrement($feature_add_removals);
                }
                $feature_ids_to_add = [];
                foreach ($feature_add_removals as $feature_removal) {
                    $feature_ids_to_add[] = $feature_removal->feature_id;
                }
                $this->explicit_top_backlog_dao->addArtifactsToTheExplicitTopBacklog($feature_ids_to_add);
            }

            $feature_remove_removals = $this->filterFeaturesThatCanBeManipulated(
                $top_backlog_change->potential_features_id_to_remove,
                $user,
                $program
            );

            if (count($feature_remove_removals) > 0) {
                $feature_ids_to_remove = [];
                foreach ($feature_remove_removals as $feature_removal) {
                    $feature_ids_to_remove[] = $feature_removal->feature_id;
                }
                $this->explicit_top_backlog_dao->removeArtifactsFromExplicitTopBacklog($feature_ids_to_remove);
            }

            if ($top_backlog_change->elements_to_order) {
                $this->features_rank_orderer->reorder(
                    $top_backlog_change->elements_to_order,
                    (string) $program->getId(),
                    $program
                );
            }
        });
    }

    /**
     * @param FeatureRemoval[] $feature_removals
     * @throws Tracker_NoArtifactLinkFieldException
     * @throws \Tracker_Exception
     */
    private function removeFeaturesFromProgramIncrement(array $feature_removals): void
    {
        foreach ($feature_removals as $feature_removal) {
            $this->feature_removal_processor->removeFromAllProgramIncrements($feature_removal);
        }
    }

    /**
     * @param int[] $features_id
     * @return FeatureRemoval[]
     * @throws FeatureHasPlannedUserStoryException
     */
    private function filterFeaturesThatCanBeManipulated(array $features_id, \PFUser $user, Program $program): array
    {
        $filtered_features = [];

        foreach ($features_id as $feature_id) {
            $feature = FeatureIdentifier::fromId($this->visible_feature_verifier, $feature_id, $user, $program);
            if (! $feature) {
                continue;
            }
            $filtered_features[] = FeatureRemoval::fromFeature(
                $this->story_verifier,
                $feature,
                $user
            );
        }

        return $filtered_features;
    }
}
