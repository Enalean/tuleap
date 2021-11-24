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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\Content;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\UserStory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\VerifyIsIteration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveTrackerFromUserStory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveUserStoryCrossRef;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveUserStoryTitle;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveUserStoryURI;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\UserStoryIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\VerifyIsOpen;
use Tuleap\ProgramManagement\Domain\Program\Feature\RetrieveBackgroundColor;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\SearchMirroredTimeboxes;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class IterationContentSearcher
{
    public function __construct(
        private VerifyIsIteration $verify_is_iteration,
        private VerifyIsVisibleArtifact $is_visible_artifact,
        private SearchUserStoryPlannedInIteration $search_user_story_planned_in_iteration,
        private RetrieveUserStoryTitle $retrieve_title_value,
        private RetrieveUserStoryURI $retrieve_uri,
        private RetrieveUserStoryCrossRef $retrieve_cross_ref,
        private VerifyIsOpen $retrieve_is_open,
        private RetrieveBackgroundColor $retrieve_background_color,
        private RetrieveTrackerFromUserStory $retrieve_tracker_id,
        private SearchMirroredTimeboxes $iteration_searcher,
    ) {
    }

    /**
     * @return UserStory[]
     *
     * @throws IterationNotFoundException
     */
    public function retrievePlannedUserStories(int $id, UserIdentifier $user_identifier): array
    {
        $iteration_identifier = IterationIdentifier::fromId($this->verify_is_iteration, $this->is_visible_artifact, $id, $user_identifier);
        if (! $iteration_identifier) {
            throw new IterationNotFoundException($id);
        }

        $content = [];

        $planned_user_stories = UserStoryIdentifier::buildCollectionFromIteration(
            $this->search_user_story_planned_in_iteration,
            $this->iteration_searcher,
            $this->is_visible_artifact,
            $iteration_identifier,
            $user_identifier
        );

        foreach ($planned_user_stories as $user_story) {
            $content[] = UserStory::build(
                $this->retrieve_title_value,
                $this->retrieve_uri,
                $this->retrieve_cross_ref,
                $this->retrieve_is_open,
                $this->retrieve_background_color,
                $this->retrieve_tracker_id,
                $user_story,
                $user_identifier
            );
        }

        return $content;
    }
}
