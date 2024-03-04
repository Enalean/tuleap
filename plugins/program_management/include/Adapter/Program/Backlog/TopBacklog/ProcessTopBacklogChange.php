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

use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Content\FeatureRemovalProcessor;
use Tuleap\ProgramManagement\Domain\Permissions\PermissionBypass;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\VerifyHasAtLeastOnePlannedUserStory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureHasPlannedUserStoryException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\VerifyFeatureIsVisibleByProgram;
use Tuleap\ProgramManagement\Domain\Program\Backlog\NotAllowedToPrioritizeException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\FeatureRemoval;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\RemoveFeatureException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Rank\OrderFeatureRank;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\CannotManipulateTopBacklog;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogChange;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogChangeProcessor;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogStore;
use Tuleap\ProgramManagement\Domain\Program\Plan\VerifyPrioritizeFeaturesPermission;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\UserCanPrioritize;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class ProcessTopBacklogChange implements TopBacklogChangeProcessor
{
    private TopBacklogStore $top_backlog_store;
    private VerifyPrioritizeFeaturesPermission $prioritize_features_permission_verifier;
    private OrderFeatureRank $features_rank_orderer;
    private VerifyHasAtLeastOnePlannedUserStory $story_verifier;
    private VerifyFeatureIsVisibleByProgram $visible_feature_verifier;
    private FeatureRemovalProcessor $feature_removal_processor;

    public function __construct(
        VerifyPrioritizeFeaturesPermission $prioritize_features_permission_verifier,
        TopBacklogStore $top_backlog_store,
        OrderFeatureRank $features_rank_orderer,
        VerifyHasAtLeastOnePlannedUserStory $story_verifier,
        VerifyFeatureIsVisibleByProgram $visible_feature_verifier,
        FeatureRemovalProcessor $feature_removal_processor,
    ) {
        $this->prioritize_features_permission_verifier = $prioritize_features_permission_verifier;
        $this->top_backlog_store                       = $top_backlog_store;
        $this->features_rank_orderer                   = $features_rank_orderer;
        $this->story_verifier                          = $story_verifier;
        $this->visible_feature_verifier                = $visible_feature_verifier;
        $this->feature_removal_processor               = $feature_removal_processor;
    }

    public function processTopBacklogChangeForAProgram(
        ProgramIdentifier $program,
        TopBacklogChange $top_backlog_change,
        UserIdentifier $user_identifier,
        ?PermissionBypass $bypass,
    ): void {
        try {
            $user_can_prioritize = UserCanPrioritize::fromUser(
                $this->prioritize_features_permission_verifier,
                $user_identifier,
                $program,
                $bypass
            );
        } catch (NotAllowedToPrioritizeException $e) {
            throw new CannotManipulateTopBacklog($program, $user_identifier);
        }

        $feature_add_removals = $this->filterFeaturesThatCanBeManipulated(
            $top_backlog_change->potential_features_id_to_add,
            $user_can_prioritize,
            $program,
            false,
            $bypass
        );

        if (count($feature_add_removals) > 0) {
            if ($top_backlog_change->remove_program_increments_link_to_feature_to_add) {
                $this->removeFeaturesFromProgramIncrement($feature_add_removals);
            }
            $feature_ids_to_add = [];
            foreach ($feature_add_removals as $feature_removal) {
                $feature_ids_to_add[] = $feature_removal->feature_id;
            }
            $this->top_backlog_store->addArtifactsToTheExplicitTopBacklog($feature_ids_to_add);
        }

        $feature_remove_removals = $this->filterFeaturesThatCanBeManipulated(
            $top_backlog_change->potential_features_id_to_remove,
            $user_can_prioritize,
            $program,
            true,
            $bypass
        );

        if (count($feature_remove_removals) > 0) {
            $feature_ids_to_remove = [];
            foreach ($feature_remove_removals as $feature_removal) {
                $feature_ids_to_remove[] = $feature_removal->feature_id;
            }
            $this->top_backlog_store->removeArtifactsFromExplicitTopBacklog($feature_ids_to_remove);
        }

        if ($top_backlog_change->elements_to_order) {
            $this->features_rank_orderer->reorder(
                $top_backlog_change->elements_to_order,
                (string) $program->getId(),
                $program
            );
        }
    }

    /**
     * @param FeatureRemoval[] $feature_removals
     * @throws RemoveFeatureException
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
     * @throws FeatureNotFoundException
     */
    private function filterFeaturesThatCanBeManipulated(
        array $features_id,
        UserCanPrioritize $user,
        ProgramIdentifier $program,
        bool $ignore_feature_cannot_be_retrieved,
        ?PermissionBypass $bypass,
    ): array {
        $filtered_features = [];

        foreach ($features_id as $feature_id) {
            $feature = FeatureIdentifier::fromIdAndProgram($this->visible_feature_verifier, $feature_id, $user, $program, $bypass);
            if (! $feature) {
                if ($ignore_feature_cannot_be_retrieved) {
                    continue;
                }
                throw new FeatureNotFoundException($feature_id);
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
