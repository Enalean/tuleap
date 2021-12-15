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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Backlog;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifierCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\SearchChildrenOfFeature;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\UserStory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\VerifyFeatureIsVisible;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\Content\SearchUserStoryPlannedInIteration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifierCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\SearchIterations;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\SearchFeatures;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\VerifyIsProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveTrackerFromUserStory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveUserStoryCrossRef;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveUserStoryTitle;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveUserStoryURI;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\UserStoryIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\UserStoryIdentifierCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\VerifyIsOpen;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\VerifyUserStoryIsVisible;
use Tuleap\ProgramManagement\Domain\Program\Feature\RetrieveBackgroundColor;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MirroredIterationIdentifierCollection;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\SearchMirroredTimeboxes;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class BacklogSearcher
{
    public function __construct(
        private VerifyIsProgramIncrement $program_increment_verifier,
        private VerifyIsVisibleArtifact $visibility_verifier,
        private SearchFeatures $feature_searcher,
        private VerifyFeatureIsVisible $feature_verifier,
        private SearchChildrenOfFeature $story_searcher,
        private VerifyUserStoryIsVisible $story_verifier,
        private SearchIterations $iteration_searcher,
        private SearchMirroredTimeboxes $mirror_searcher,
        private SearchUserStoryPlannedInIteration $planned_story_searcher,
        private RetrieveUserStoryTitle $title_retriever,
        private RetrieveUserStoryURI $uri_retriever,
        private RetrieveUserStoryCrossRef $cross_reference_retriever,
        private VerifyIsOpen $open_verifier,
        private RetrieveBackgroundColor $background_color_retriever,
        private RetrieveTrackerFromUserStory $story_tracker_retriever,
    ) {
    }

    /**
     * @return UserStory[]
     * @throws ProgramIncrementNotFoundException
     */
    public function searchUnplannedUserStories(int $program_increment_id, UserIdentifier $user): array
    {
        /*
         * get the features (content) of the program increment
         * get the user stories of all the features (1)
         * get the iterations of the program increment
         * get the mirrors of all the iterations
         * get the user stories (content) of all the mirrors (2)
         * iterate over all user stories from (1) and filter out all user stories from (2).
         * this gives us as result the user stories that are not planned in any mirror
         */

        $program_increment = ProgramIncrementIdentifier::fromId(
            $this->program_increment_verifier,
            $this->visibility_verifier,
            $program_increment_id,
            $user
        );

        $features = FeatureIdentifierCollection::fromProgramIncrement(
            $this->feature_searcher,
            $this->feature_verifier,
            $program_increment,
            $user
        );

        $all_user_stories = UserStoryIdentifierCollection::fromFeatureCollection(
            $this->story_searcher,
            $this->story_verifier,
            $features,
            $user
        );

        $iterations = IterationIdentifierCollection::fromProgramIncrement(
            $this->iteration_searcher,
            $this->visibility_verifier,
            $program_increment,
            $user
        );

        $mirror_iterations = MirroredIterationIdentifierCollection::fromIterationCollection(
            $this->mirror_searcher,
            $this->visibility_verifier,
            $iterations,
            $user
        );

        $planned_user_stories = UserStoryIdentifierCollection::fromMirroredIterationCollection(
            $this->planned_story_searcher,
            $this->story_verifier,
            $mirror_iterations,
            $user
        );

        $unplanned_user_stories = $all_user_stories->difference($planned_user_stories);

        return array_map(
            fn(UserStoryIdentifier $story_identifier) => UserStory::build(
                $this->title_retriever,
                $this->uri_retriever,
                $this->cross_reference_retriever,
                $this->open_verifier,
                $this->background_color_retriever,
                $this->story_tracker_retriever,
                $story_identifier,
                $user
            ),
            $unplanned_user_stories->getUserStories()
        );
    }
}
