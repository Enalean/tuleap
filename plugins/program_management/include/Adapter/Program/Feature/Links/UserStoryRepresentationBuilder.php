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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Feature\Links;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\Links\FeatureIsNotPlannableException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\FeatureNotAccessException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\SearchChildrenOfFeature;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\UserStory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\VerifyIsVisibleFeature;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\VerifyIsOpen;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveTrackerId;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveUserStoryCrossRef;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveUserStoryTitle;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveUserStoryURI;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\UserStoryIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Feature\RetrieveBackgroundColor;
use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\Plan\VerifyIsPlannable;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\REST\v1\UserStoryRepresentation;

final class UserStoryRepresentationBuilder
{
    public function __construct(
        private SearchChildrenOfFeature $search_children_of_feature,
        private \Tracker_ArtifactFactory $artifact_factory,
        private VerifyIsPlannable $verify_is_plannable,
        private RetrieveBackgroundColor $retrieve_background_color,
        private RetrieveUser $retrieve_user,
        private \TrackerFactory $tracker_factory,
        private VerifyIsVisibleFeature $verify_is_visible_feature,
        private BuildProgram $build_program,
        private RetrieveUserStoryTitle $retrieve_title_value,
        private RetrieveUserStoryURI $retrieve_uri,
        private RetrieveUserStoryCrossRef $retrieve_cross_ref,
        private VerifyIsOpen $retrieve_is_open,
        private RetrieveTrackerId $retrieve_tracker_id,
        private VerifyIsVisibleArtifact $verify_is_visible_artifact
    ) {
    }

    /**
     * @return UserStoryRepresentation[]
     * @throws FeatureNotAccessException
     * @throws FeatureIsNotPlannableException
     */
    public function buildFeatureStories(int $feature_id, UserIdentifier $user_identifier): array
    {
        $user          = $this->retrieve_user->getUserWithId($user_identifier);
        $full_artifact = $this->artifact_factory->getArtifactByIdUserCanView($user, $feature_id);
        if (! $full_artifact) {
            throw new FeatureNotAccessException();
        }

        try {
            $program = ProgramIdentifier::fromId(
                $this->build_program,
                (int) $full_artifact->getTracker()->getGroupId(),
                $user_identifier,
                null
            );
        } catch (ProgramAccessException | ProjectIsNotAProgramException $e) {
            throw new FeatureNotAccessException();
        }
        $feature = FeatureIdentifier::fromId(
            $this->verify_is_visible_feature,
            $feature_id,
            $user_identifier,
            $program,
            null
        );
        if (! $feature) {
            throw new FeatureNotAccessException();
        }

        $feature_tracker_is_plannable = $this->verify_is_plannable->isPlannable($full_artifact->getTrackerId());

        if (! $feature_tracker_is_plannable) {
            throw new FeatureIsNotPlannableException($full_artifact->getTrackerId());
        }

        $linked_children  = [];
        $planned_children = UserStoryIdentifier::buildCollectionFromFeature(
            $this->search_children_of_feature,
            $this->verify_is_visible_artifact,
            $feature,
            $user_identifier
        );

        foreach ($planned_children as $planned_child) {
            $user_story = UserStory::build(
                $this->retrieve_title_value,
                $this->retrieve_uri,
                $this->retrieve_cross_ref,
                $this->retrieve_is_open,
                $this->retrieve_background_color,
                $this->retrieve_tracker_id,
                $planned_child,
                $user_identifier
            );

            $user_story_representation = UserStoryRepresentation::build(
                $this->tracker_factory,
                $user_story
            );
            if ($user_story_representation) {
                $linked_children[] = $user_story_representation;
            }
        }

        return $linked_children;
    }
}
