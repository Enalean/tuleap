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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifierCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\SearchChildrenOfFeature;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\Content\SearchUserStoryPlannedInIteration;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MirroredIterationIdentifierCollection;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * @psalm-immutable
 */
final class UserStoryIdentifierCollection
{
    /**
     * @param UserStoryIdentifier[] $user_stories
     */
    private function __construct(private array $user_stories)
    {
    }

    public function getUserStories(): array
    {
        return $this->user_stories;
    }

    public static function fromFeatureCollection(
        SearchChildrenOfFeature $user_stories_searcher,
        VerifyUserStoryIsVisible $visibility_verifier,
        FeatureIdentifierCollection $features,
        UserIdentifier $user,
    ): self {
        $all_stories = [];
        foreach ($features->getFeatures() as $feature) {
            $stories_of_feature = UserStoryIdentifier::buildCollectionFromFeature(
                $user_stories_searcher,
                $visibility_verifier,
                $feature,
                $user
            );
            array_push($all_stories, ...$stories_of_feature);
        }
        return new self($all_stories);
    }

    public static function fromMirroredIterationCollection(
        SearchUserStoryPlannedInIteration $user_stories_searcher,
        VerifyUserStoryIsVisible $visibility_verifier,
        MirroredIterationIdentifierCollection $mirror_iterations,
        UserIdentifier $user,
    ): self {
        $user_stories = UserStoryIdentifier::buildCollectionFromIteration(
            $user_stories_searcher,
            $visibility_verifier,
            $mirror_iterations,
            $user
        );
        return new self($user_stories);
    }

    /**
     * Remove all User Stories from $this that are present in $other_collection.
     * @return self Filtered collection of User Stories
     */
    public function difference(self $other_collection): self
    {
        $difference = array_udiff(
            $this->user_stories,
            $other_collection->user_stories,
            static fn(
                UserStoryIdentifier $story_a,
                UserStoryIdentifier $story_b,
            ) => $story_a->getId() - $story_b->getId()
        );
        return new self(array_values($difference));
    }
}
