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

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\Links\FeatureIsNotPlannableException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\PlannableFeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\VerifyFeatureIsVisible;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\VerifyIsOpen;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveTrackerFromUserStory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveUserStoryCrossRef;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveUserStoryTitle;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveUserStoryURI;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\UserStoryIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Feature\RetrieveBackgroundColor;
use Tuleap\ProgramManagement\Domain\Program\Plan\VerifyIsPlannable;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\RetrieveTrackerOfArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class UserStoryRetriever
{
    public function __construct(
        private SearchChildrenOfFeature $search_children_of_feature,
        private VerifyIsPlannable $verify_is_plannable,
        private RetrieveBackgroundColor $retrieve_background_color,
        private VerifyFeatureIsVisible $verify_is_visible_feature,
        private RetrieveUserStoryTitle $retrieve_title_value,
        private RetrieveUserStoryURI $retrieve_uri,
        private RetrieveUserStoryCrossRef $retrieve_cross_ref,
        private VerifyIsOpen $retrieve_is_open,
        private RetrieveTrackerFromUserStory $retrieve_tracker_id,
        private VerifyIsVisibleArtifact $verify_is_visible_artifact,
        private RetrieveTrackerOfArtifact $retrieve_tracker_of_artifact,
    ) {
    }

    /**
     * @return UserStory[]
     * @throws FeatureNotAccessException
     * @throws FeatureIsNotPlannableException
     */
    public function retrieveStories(int $feature_id, UserIdentifier $user_identifier): array
    {
        $feature = FeatureIdentifier::fromId(
            $this->verify_is_visible_feature,
            $feature_id,
            $user_identifier,
        );
        if (! $feature) {
            throw new FeatureNotAccessException();
        }

        $plannable_feature = PlannableFeatureIdentifier::build(
            $this->verify_is_plannable,
            $this->retrieve_tracker_of_artifact,
            $feature
        );

        $user_stories     = [];
        $planned_children = UserStoryIdentifier::buildCollectionFromFeature(
            $this->search_children_of_feature,
            $this->verify_is_visible_artifact,
            $plannable_feature,
            $user_identifier
        );

        foreach ($planned_children as $planned_child) {
            $user_stories[] = UserStory::build(
                $this->retrieve_title_value,
                $this->retrieve_uri,
                $this->retrieve_cross_ref,
                $this->retrieve_is_open,
                $this->retrieve_background_color,
                $this->retrieve_tracker_id,
                $planned_child,
                $user_identifier
            );
        }

        return $user_stories;
    }
}
