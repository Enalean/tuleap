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

namespace Tuleap\ProgramManagement\Domain\Team\MirroredTimebox;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\FeatureHasUserStoriesVerifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\VerifyHasAtLeastOnePlannedUserStory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Feature;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIsNotPlannableException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\SearchParentFeatureOfAUserStory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\RetrieveFeatureCrossReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\RetrieveFeatureTitle;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\RetrieveFeatureURI;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\RetrieveTrackerOfFeature;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\UserStoryIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Feature\CheckIsValidFeature;
use Tuleap\ProgramManagement\Domain\Program\Feature\RetrieveBackgroundColor;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class FeatureOfUserStoryRetriever
{
    public function __construct(
        private RetrieveFeatureTitle $title_retriever,
        private RetrieveFeatureURI $uri_retriever,
        private RetrieveFeatureCrossReference $cross_reference_retriever,
        private VerifyHasAtLeastOnePlannedUserStory $planned_verifier,
        private CheckIsValidFeature $check_is_valid_feature,
        private RetrieveBackgroundColor $background_retriever,
        private RetrieveTrackerOfFeature $tracker_retriever,
        private SearchParentFeatureOfAUserStory $search_parent_feature_of_a_user_story,
        private FeatureHasUserStoriesVerifier $feature_has_user_stories_verifier,
    ) {
    }


    public function retrieveFeature(UserStoryIdentifier $story_identifier, UserIdentifier $user_identifier): ?Feature
    {
        $feature_id = $this->search_parent_feature_of_a_user_story->getParentOfUserStory($story_identifier);
        if ($feature_id === null) {
            return null;
        }

        try {
            $feature_identifier = FeatureIdentifier::fromId(
                $this->check_is_valid_feature,
                $feature_id,
                $user_identifier
            );
        } catch (FeatureIsNotPlannableException | FeatureNotFoundException $e) {
            return null;
        }

        return Feature::fromFeatureIdentifier(
            $this->title_retriever,
            $this->uri_retriever,
            $this->cross_reference_retriever,
            $this->planned_verifier,
            $this->feature_has_user_stories_verifier,
            $this->background_retriever,
            $this->tracker_retriever,
            $feature_identifier,
            $user_identifier
        );
    }
}
