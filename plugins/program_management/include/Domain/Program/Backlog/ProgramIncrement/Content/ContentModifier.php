<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureCanNotBeRankedWithItselfException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureHasPlannedUserStoryException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\VerifyFeatureIsVisibleByProgram;
use Tuleap\ProgramManagement\Domain\Program\Backlog\NotAllowedToPrioritizeException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\UserCanPlanInProgramIncrementVerifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\VerifyFeatureIsPlannedInProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\VerifyIsProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Rank\OrderFeatureRank;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\FeaturesToReorder;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\FeatureCannotBePlannedInProgramIncrementException;
use Tuleap\ProgramManagement\Domain\Program\Plan\InvalidFeatureIdInProgramIncrementException;
use Tuleap\ProgramManagement\Domain\Program\Plan\VerifyPrioritizeFeaturesPermission;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\RetrieveProgramOfProgramIncrement;
use Tuleap\ProgramManagement\Domain\UserCanPrioritize;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class ContentModifier implements ModifyContent
{
    public function __construct(
        private VerifyPrioritizeFeaturesPermission $permission_verifier,
        private VerifyIsProgramIncrement $program_increment_verifier,
        private VerifyFeatureIsVisibleByProgram $visible_verifier,
        private VerifyCanBePlannedInProgramIncrement $can_be_planned_verifier,
        private FeaturePlanner $feature_planner,
        private OrderFeatureRank $features_rank_orderer,
        private VerifyFeatureIsPlannedInProgramIncrement $feature_is_planned_verifier,
        private UserCanPlanInProgramIncrementVerifier $can_plan_in_program_increment_verifier,
        private VerifyIsVisibleArtifact $visibility_verifier,
        private RetrieveProgramOfProgramIncrement $program_retriever,
        private BuildProgram $program_builder,
    ) {
    }

    #[\Override]
    public function modifyContent(int $program_increment_id, ContentChange $content_change, UserIdentifier $user): void
    {
        if ($content_change->potential_feature_id_to_add === null && $content_change->elements_to_order === null) {
            throw new AddOrOrderMustBeSetException();
        }
        $program_increment   = ProgramIncrementIdentifier::fromId(
            $this->program_increment_verifier,
            $this->visibility_verifier,
            $program_increment_id,
            $user
        );
        $program             = ProgramIdentifier::fromProgramIncrement(
            $this->program_retriever,
            $this->program_builder,
            $program_increment,
            $user
        );
        $user_can_prioritize = UserCanPrioritize::fromUser(
            $this->permission_verifier,
            $user,
            $program,
            null
        );

        if ($content_change->potential_feature_id_to_add !== null) {
            $this->planFeature(
                $content_change->potential_feature_id_to_add,
                $program_increment,
                $user_can_prioritize,
                $program
            );
        }
        if ($content_change->elements_to_order !== null) {
            $this->reorderFeature($content_change->elements_to_order, $program_increment, $program, $user_can_prioritize);
        }
    }

    /**
     * @throws FeatureCannotBePlannedInProgramIncrementException
     * @throws FeatureHasPlannedUserStoryException
     * @throws AddFeatureException
     * @throws ProgramIncrementNotFoundException
     * @throws RemoveFeatureException
     * @throws FeatureNotFoundException
     */
    private function planFeature(
        int $potential_feature_id_to_add,
        ProgramIncrementIdentifier $program_increment,
        UserCanPrioritize $user,
        ProgramIdentifier $program,
    ): void {
        $feature = FeatureIdentifier::fromIdAndProgram($this->visible_verifier, $potential_feature_id_to_add, $user, $program, null);
        if ($feature === null) {
            throw new FeatureNotFoundException($potential_feature_id_to_add);
        }
        $feature_addition = FeatureAddition::fromFeature(
            $this->can_be_planned_verifier,
            $feature,
            $program_increment,
            $user
        );
        $this->feature_planner->plan($feature_addition);
    }

    /**
     * @throws FeatureCanNotBeRankedWithItselfException
     * @throws InvalidFeatureIdInProgramIncrementException
     * @throws NotAllowedToPrioritizeException
     */
    private function reorderFeature(
        FeaturesToReorder $feature_to_order_representation,
        ProgramIncrementIdentifier $program_increment,
        ProgramIdentifier $program,
        UserCanPrioritize $user,
    ): void {
        $this->checkFeatureCanBeReordered($feature_to_order_representation->getIds()[0], $program_increment, $user);
        $this->checkFeatureCanBeReordered($feature_to_order_representation->getComparedTo(), $program_increment, $user);
        $this->features_rank_orderer->reorder($feature_to_order_representation, (string) $program_increment->getId(), $program);
    }

    /**
     * @throws InvalidFeatureIdInProgramIncrementException
     * @throws NotAllowedToPrioritizeException
     */
    private function checkFeatureCanBeReordered(
        int $potential_feature_id_to_manipulate,
        ProgramIncrementIdentifier $program_increment,
        UserCanPrioritize $user,
    ): void {
        $can_be_planned = $this->can_be_planned_verifier->canBePlannedInProgramIncrement(
            $potential_feature_id_to_manipulate,
            $program_increment->getId()
        );
        if (! $can_be_planned) {
            throw new InvalidFeatureIdInProgramIncrementException(
                $potential_feature_id_to_manipulate,
                $program_increment->getId()
            );
        }
        $feature_is_planned = $this->feature_is_planned_verifier->isFeaturePlannedInProgramIncrement($program_increment->getId(), $potential_feature_id_to_manipulate);

        if (! $feature_is_planned) {
            throw new InvalidFeatureIdInProgramIncrementException(
                $potential_feature_id_to_manipulate,
                $program_increment->getId()
            );
        }

        if (! $this->can_plan_in_program_increment_verifier->userCanPlanAndPrioritize($program_increment, $user)) {
            throw new NotAllowedToPrioritizeException($user->getId(), $program_increment->getId());
        }
    }
}
