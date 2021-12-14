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

use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Artifact\RetrieveFullArtifact;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\VerifyFeatureHasAtLeastOneUserStory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\VerifyHasAtLeastOnePlannedUserStory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\VerifyFeatureIsVisibleByProgram;
use Tuleap\ProgramManagement\Domain\Program\Feature\RetrieveBackgroundColor;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\REST\v1\FeatureRepresentation;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;

final class FeatureRepresentationBuilder
{
    public function __construct(
        private RetrieveFullArtifact $artifact_retriever,
        private \Tracker_FormElementFactory $form_element_factory,
        private RetrieveBackgroundColor $retrieve_background_color,
        private VerifyFeatureIsVisibleByProgram $feature_verifier,
        private VerifyHasAtLeastOnePlannedUserStory $planned_user_story_verifier,
        private VerifyFeatureHasAtLeastOneUserStory $story_verifier,
        private RetrieveUser $retrieve_user,
    ) {
    }

    public function buildFeatureRepresentation(
        UserIdentifier $user_identifier,
        ProgramIdentifier $program,
        int $artifact_id,
        int $title_field_id,
        string $artifact_title,
    ): ?FeatureRepresentation {
        $user = $this->retrieve_user->getUserWithId($user_identifier);

        $feature = FeatureIdentifier::fromIdAndProgram($this->feature_verifier, $artifact_id, $user_identifier, $program, null);
        if (! $feature) {
            return null;
        }
        $full_artifact = $this->artifact_retriever->getNonNullArtifact($feature);

        $title = $this->form_element_factory->getFieldById($title_field_id);
        if (! $title || ! $title->userCanRead($user)) {
            return null;
        }

        return new FeatureRepresentation(
            $feature->getId(),
            $artifact_title,
            $full_artifact->getXRef(),
            $full_artifact->getUri(),
            MinimalTrackerRepresentation::build($full_artifact->getTracker()),
            $this->retrieve_background_color->retrieveBackgroundColor($feature, $user_identifier),
            $this->planned_user_story_verifier->hasAtLeastOnePlannedUserStory($feature, $user_identifier),
            $this->story_verifier->hasStoryLinked($feature, $user_identifier)
        );
    }
}
