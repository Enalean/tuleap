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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature;

use Psr\Log\LoggerInterface;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\FeaturePlanChange;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\SearchFeaturesInChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\PlanUserStoriesInMirroredProgramIncrements;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\ProgramIncrementChanged;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\SearchArtifactsLinks;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\SearchFeatures;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Feature\Links\LinkedFeaturesDiff;
use Tuleap\ProgramManagement\Domain\Program\Feature\PlanUserStoryInOneMirror;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MirroredProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrieveMirroredProgramIncrementFromTeam;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\SearchMirroredTimeboxes;
use Tuleap\ProgramManagement\Domain\Team\TeamIdentifier;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;

final class UserStoriesInMirroredProgramIncrementsPlanner implements PlanUserStoriesInMirroredProgramIncrements
{
    public function __construct(
        private DBTransactionExecutor $db_transaction_executor,
        private SearchArtifactsLinks $artifacts_links_search,
        private SearchMirroredTimeboxes $mirrored_timeboxes_searcher,
        private VerifyIsVisibleArtifact $visibility_verifier,
        private SearchFeatures $features_searcher,
        private LoggerInterface $logger,
        private SearchFeaturesInChangeset $search_features_in_changeset,
        private RetrieveMirroredProgramIncrementFromTeam $retrieve_mirrored_program_increment_from_team,
        private PlanUserStoryInOneMirror $story_in_one_mirror_planner,
    ) {
    }

    #[\Override]
    public function plan(ProgramIncrementChanged $program_increment_changed): void
    {
        $this->logger->debug('Check if we need to plan/unplan items in mirrored releases.');
        $program_increment   = $program_increment_changed->program_increment;
        $user_identifier     = $program_increment_changed->user;
        $feature_plan_change = $this->getPlanChange($program_increment, $program_increment_changed);

        $this->db_transaction_executor->execute(
            function () use ($feature_plan_change, $user_identifier, $program_increment) {
                $mirrored_program_increments = MirroredProgramIncrementIdentifier::buildCollectionOnlyWhenUserCanSee(
                    $this->mirrored_timeboxes_searcher,
                    $this->visibility_verifier,
                    $program_increment,
                    $user_identifier
                );
                foreach ($mirrored_program_increments as $mirrored_program_increment) {
                    $this->story_in_one_mirror_planner->planInOneMirror(
                        $program_increment,
                        $mirrored_program_increment,
                        $feature_plan_change,
                        $user_identifier
                    );
                }
            }
        );
    }

    #[\Override]
    public function planForATeam(ProgramIncrementChanged $program_increment_changed, TeamIdentifier $team_identifier): void
    {
        $this->logger->debug('Check if we need to plan/unplan items in mirrored releases.');

        $program_increment_identifier = $program_increment_changed->program_increment;
        $user_identifier              = $program_increment_changed->user;
        $feature_plan_change          = $this->getPlanChange($program_increment_identifier, $program_increment_changed);

        $mirrored_program_increment = MirroredProgramIncrementIdentifier::fromProgramIncrementAndTeam(
            $this->retrieve_mirrored_program_increment_from_team,
            $this->visibility_verifier,
            $program_increment_identifier,
            $team_identifier,
            $user_identifier
        );

        if (! $mirrored_program_increment) {
            $this->logger->error(sprintf('Mirrored of program increment with id %d not found', $program_increment_identifier->getId()));
            return;
        }

        $this->story_in_one_mirror_planner->planInOneMirror(
            $program_increment_identifier,
            $mirrored_program_increment,
            $feature_plan_change,
            $user_identifier
        );
    }

    private function getPlanChange(ProgramIncrementIdentifier $program_increment_identifier, ProgramIncrementChanged $program_increment_changed): FeaturePlanChange
    {
        $potential_feature_to_link = $this->features_searcher->searchFeatures($program_increment_identifier);
        $features_diff             = LinkedFeaturesDiff::build(
            $this->search_features_in_changeset,
            $program_increment_changed
        );
        return FeaturePlanChange::fromRaw(
            $this->artifacts_links_search,
            $potential_feature_to_link,
            $features_diff->getRemovedFeaturesIds(),
            $program_increment_changed->tracker->getId()
        );
    }
}
