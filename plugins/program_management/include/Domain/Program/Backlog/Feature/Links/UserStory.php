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

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\BackgroundColor;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Feature;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\VerifyIsOpen;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveTrackerFromUserStory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveUserStoryCrossRef;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveUserStoryTitle;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveUserStoryURI;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\UserStoryIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Feature\RetrieveBackgroundColor;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\FeatureOfUserStoryRetriever;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * @psalm-immutable
 */
final class UserStory
{
    private function __construct(
        public UserStoryIdentifier $user_story_identifier,
        public string $uri,
        public string $cross_ref,
        public ?string $title,
        public bool $is_open,
        public UserStoryTrackerIdentifier $tracker_identifier,
        public BackgroundColor $background_color,
        public ?Feature $feature,
    ) {
    }

    /**
     * @throws \Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIsNotPlannableException
     * @throws \Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureNotFoundException
     */
    public static function build(
        RetrieveUserStoryTitle $retrieve_title_value,
        RetrieveUserStoryURI $retrieve_uri,
        RetrieveUserStoryCrossRef $retrieve_cross_ref,
        VerifyIsOpen $retrieve_is_open,
        RetrieveBackgroundColor $retrieve_background_color,
        RetrieveTrackerFromUserStory $retrieve_tracker_id,
        UserStoryIdentifier $user_story_identifier,
        UserIdentifier $user_identifier,
    ): self {
        return new self(
            $user_story_identifier,
            $retrieve_uri->getUserStoryURI($user_story_identifier),
            $retrieve_cross_ref->getUserStoryCrossRef($user_story_identifier),
            $retrieve_title_value->getUserStoryTitle($user_story_identifier, $user_identifier),
            $retrieve_is_open->isOpen($user_story_identifier),
            UserStoryTrackerIdentifier::fromUserStory($retrieve_tracker_id, $user_story_identifier),
            $retrieve_background_color->retrieveBackgroundColor($user_story_identifier, $user_identifier),
            null,
        );
    }

    /**
     * @throws \Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIsNotPlannableException
     * @throws \Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureNotFoundException
     */
    public static function buildWithParentFeature(
        RetrieveUserStoryTitle $retrieve_title_value,
        RetrieveUserStoryURI $retrieve_uri,
        RetrieveUserStoryCrossRef $retrieve_cross_ref,
        VerifyIsOpen $retrieve_is_open,
        RetrieveBackgroundColor $retrieve_background_color,
        RetrieveTrackerFromUserStory $retrieve_tracker_id,
        FeatureOfUserStoryRetriever $retrieve_feature_of_user_story,
        UserStoryIdentifier $user_story_identifier,
        UserIdentifier $user_identifier,
    ): self {
        return new self(
            $user_story_identifier,
            $retrieve_uri->getUserStoryURI($user_story_identifier),
            $retrieve_cross_ref->getUserStoryCrossRef($user_story_identifier),
            $retrieve_title_value->getUserStoryTitle($user_story_identifier, $user_identifier),
            $retrieve_is_open->isOpen($user_story_identifier),
            UserStoryTrackerIdentifier::fromUserStory($retrieve_tracker_id, $user_story_identifier),
            $retrieve_background_color->retrieveBackgroundColor($user_story_identifier, $user_identifier),
            $retrieve_feature_of_user_story->retrieveFeature($user_story_identifier, $user_identifier),
        );
    }
}
