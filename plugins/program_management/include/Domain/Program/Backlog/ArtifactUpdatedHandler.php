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

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\PlanUserStoriesInMirroredProgramIncrements;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\ProgramIncrementChanged;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\VerifyIsProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\RemovePlannedFeaturesFromTopBacklog;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Event\ArtifactUpdated;

final class ArtifactUpdatedHandler
{
    private VerifyIsProgramIncrementTracker $program_increment_verifier;
    private PlanUserStoriesInMirroredProgramIncrements $user_stories_planner;
    private RemovePlannedFeaturesFromTopBacklog $feature_remover;

    public function __construct(
        VerifyIsProgramIncrementTracker $program_increment_verifier,
        PlanUserStoriesInMirroredProgramIncrements $user_stories_planner,
        RemovePlannedFeaturesFromTopBacklog $feature_remover
    ) {
        $this->program_increment_verifier = $program_increment_verifier;
        $this->user_stories_planner       = $user_stories_planner;
        $this->feature_remover            = $feature_remover;
    }

    public function handle(ArtifactUpdated $event): void
    {
        $this->planArtifactIfNeeded($event);
        $this->cleanUpFromTopBacklogFeatureAddedToAProgramIncrement($event->getArtifact());
    }

    private function planArtifactIfNeeded(ArtifactUpdated $event): void
    {
        $tracker_id = $event->getArtifact()->getTrackerId();
        if (! $this->program_increment_verifier->isProgramIncrementTracker($tracker_id)) {
            return;
        }
        $program_increment_changed = new ProgramIncrementChanged(
            $event->getArtifact()->getId(),
            $tracker_id,
            $event->getUser()
        );
        $this->user_stories_planner->plan($program_increment_changed);
    }

    private function cleanUpFromTopBacklogFeatureAddedToAProgramIncrement(Artifact $artifact): void
    {
        $this->feature_remover->removeFeaturesPlannedInAProgramIncrementFromTopBacklog($artifact->getId());
    }
}
