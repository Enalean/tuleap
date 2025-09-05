<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Workspace\Tracker;

use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Artifact\RetrieveFullArtifact;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\RetrieveTrackerOfFeature;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveTrackerFromUserStory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\UserStoryIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\Artifact\ArtifactIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\RetrieveTrackerOfArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\TrackerIdentifier;

final class TrackerOfArtifactRetriever implements RetrieveTrackerOfArtifact, RetrieveTrackerFromUserStory, RetrieveTrackerOfFeature
{
    public function __construct(private RetrieveFullArtifact $artifact_retriever)
    {
    }

    #[\Override]
    public function getTrackerOfArtifact(ArtifactIdentifier $artifact): TrackerIdentifier
    {
        $full_artifact = $this->artifact_retriever->getNonNullArtifact($artifact);
        return TrackerIdentifierProxy::fromTracker($full_artifact->getTracker());
    }

    #[\Override]
    public function getUserStoryTracker(UserStoryIdentifier $user_story_identifier): TrackerIdentifier
    {
        $artifact = $this->artifact_retriever->getNonNullArtifact($user_story_identifier);
        return TrackerIdentifierProxy::fromTracker($artifact->getTracker());
    }

    #[\Override]
    public function getFeatureTracker(FeatureIdentifier $feature): TrackerIdentifier
    {
        $artifact = $this->artifact_retriever->getNonNullArtifact($feature);
        return TrackerIdentifierProxy::fromTracker($artifact->getTracker());
    }
}
