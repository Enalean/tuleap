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

use Tuleap\ProgramManagement\Domain\Events\ArtifactUpdatedEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\IterationReplicationScheduler;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\PlanUserStoriesInMirroredProgramIncrements;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\ProgramIncrementChanged;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementUpdate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\VerifyIsProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\RemovePlannedFeaturesFromTopBacklog;

final class ArtifactUpdatedHandler
{
    public function __construct(
        private VerifyIsProgramIncrementTracker $program_increment_verifier,
        private PlanUserStoriesInMirroredProgramIncrements $user_stories_planner,
        private RemovePlannedFeaturesFromTopBacklog $feature_remover,
        private IterationReplicationScheduler $iteration_replicator
    ) {
    }

    public function handle(ArtifactUpdatedEvent $event): void
    {
        $program_increment_update = ProgramIncrementUpdate::fromArtifactUpdatedEvent(
            $this->program_increment_verifier,
            $event
        );
        if ($program_increment_update) {
            $this->planArtifactIfNeeded($program_increment_update);
            $this->iteration_replicator->replicateIterationsIfNeeded($program_increment_update);
        }
        $this->cleanUpFromTopBacklogFeatureAddedToAProgramIncrement($event);
    }

    private function planArtifactIfNeeded(ProgramIncrementUpdate $program_increment_update): void
    {
        $program_increment_changed = new ProgramIncrementChanged(
            $program_increment_update->program_increment->getId(),
            $program_increment_update->tracker->id,
            $program_increment_update->user
        );
        $this->user_stories_planner->plan($program_increment_changed);
    }

    private function cleanUpFromTopBacklogFeatureAddedToAProgramIncrement(ArtifactUpdatedEvent $artifact_updated): void
    {
        $this->feature_remover->removeFeaturesPlannedInAProgramIncrementFromTopBacklog(
            $artifact_updated->getArtifactId()
        );
    }
}
