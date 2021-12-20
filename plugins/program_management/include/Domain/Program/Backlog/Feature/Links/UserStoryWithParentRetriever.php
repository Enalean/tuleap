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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIsNotPlannableException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveTrackerFromUserStory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveUserStoryCrossRef;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveUserStoryTitle;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveUserStoryURI;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\UserStoryIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\VerifyIsOpen;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\VerifyUserStoryIsVisible;
use Tuleap\ProgramManagement\Domain\Program\Feature\CheckIsValidFeature;
use Tuleap\ProgramManagement\Domain\Program\Feature\RetrieveBackgroundColor;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\FeatureOfUserStoryRetriever;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class UserStoryWithParentRetriever
{
    public function __construct(
        private SearchChildrenOfFeature $search_children_of_feature,
        private CheckIsValidFeature $feature_verifier,
        private RetrieveBackgroundColor $retrieve_background_color,
        private RetrieveUserStoryTitle $retrieve_title_value,
        private RetrieveUserStoryURI $retrieve_uri,
        private RetrieveUserStoryCrossRef $retrieve_cross_ref,
        private VerifyIsOpen $retrieve_is_open,
        private RetrieveTrackerFromUserStory $retrieve_tracker_id,
        private VerifyUserStoryIsVisible $verify_user_story_is_visible,
        private FeatureOfUserStoryRetriever $retrieve_feature_of_user_story,
    ) {
    }

    /**
     * @return UserStory[]
     * @throws FeatureNotFoundException
     * @throws FeatureIsNotPlannableException
     */
    public function retrieveStories(int $feature_id, UserIdentifier $user_identifier): array
    {
        $feature = FeatureIdentifier::fromId(
            $this->feature_verifier,
            $feature_id,
            $user_identifier,
        );

        $planned_children = UserStoryIdentifier::buildCollectionFromFeature(
            $this->search_children_of_feature,
            $this->verify_user_story_is_visible,
            $feature,
            $user_identifier
        );

        return array_map(
            fn(UserStoryIdentifier $user_story_identifier) => UserStory::buildWithParentFeature(
                $this->retrieve_title_value,
                $this->retrieve_uri,
                $this->retrieve_cross_ref,
                $this->retrieve_is_open,
                $this->retrieve_background_color,
                $this->retrieve_tracker_id,
                $this->retrieve_feature_of_user_story,
                $user_story_identifier,
                $user_identifier
            ),
            $planned_children
        );
    }
}
