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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog;

use Tuleap\ProgramManagement\Adapter\Events\ArtifactUpdatedProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\IterationReplicationScheduler;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\PlanUserStoriesInMirroredProgramIncrements;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\ProgramIncrementChanged;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\VerifyIsProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\RemovePlannedFeaturesFromTopBacklog;

final class ArtifactUpdatedHandler
{
    private VerifyIsProgramIncrementTracker $program_increment_verifier;
    private PlanUserStoriesInMirroredProgramIncrements $user_stories_planner;
    private RemovePlannedFeaturesFromTopBacklog $feature_remover;
    private IterationReplicationScheduler $iteration_replicator;

    public function __construct(
        VerifyIsProgramIncrementTracker $program_increment_verifier,
        PlanUserStoriesInMirroredProgramIncrements $user_stories_planner,
        RemovePlannedFeaturesFromTopBacklog $feature_remover,
        IterationReplicationScheduler $iteration_replicator
    ) {
        $this->program_increment_verifier = $program_increment_verifier;
        $this->user_stories_planner       = $user_stories_planner;
        $this->feature_remover            = $feature_remover;
        $this->iteration_replicator       = $iteration_replicator;
    }

    public function handle(ArtifactUpdatedProxy $event): void
    {
        $program_increment = ProgramIncrementIdentifier::fromArtifactUpdated($this->program_increment_verifier, $event);
        if ($program_increment) {
            $this->planArtifactIfNeeded($event, $program_increment);
            $this->iteration_replicator->replicateIterationsIfNeeded($program_increment, $event->user);
        }
        $this->cleanUpFromTopBacklogFeatureAddedToAProgramIncrement($event);
    }

    private function planArtifactIfNeeded(
        ArtifactUpdatedProxy $artifact_updated,
        ProgramIncrementIdentifier $program_increment
    ): void {
        $program_increment_changed = new ProgramIncrementChanged(
            $program_increment->getId(),
            $artifact_updated->tracker_id,
            $artifact_updated->user
        );
        $this->user_stories_planner->plan($program_increment_changed);
    }

    private function cleanUpFromTopBacklogFeatureAddedToAProgramIncrement(ArtifactUpdatedProxy $artifact_updated): void
    {
        $this->feature_remover->removeFeaturesPlannedInAProgramIncrementFromTopBacklog($artifact_updated->artifact_id);
    }
}
