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

use Tuleap\ProgramManagement\Tests\Builder\FeatureIdentifierCollectionBuilder;
use Tuleap\ProgramManagement\Tests\Builder\MirroredIterationIdentifierCollectionBuilder;
use Tuleap\ProgramManagement\Tests\Builder\UserStoryIdentifierCollectionBuilder;
use Tuleap\ProgramManagement\Tests\Stub\SearchChildrenOfFeatureStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchUserStoryPlannedInIterationStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserStoryIsVisibleStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserStoryIdentifierCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_USER_STORY_ID  = 1000;
    private const SECOND_USER_STORY_ID = 36;
    private const THIRD_USER_STORY_ID  = 910;
    private const FOURTH_USER_STORY_ID = 335;
    private SearchChildrenOfFeatureStub $user_stories_searcher;
    private SearchUserStoryPlannedInIterationStub $planned_stories_searcher;
    private \Closure $getId;

    #[\Override]
    protected function setUp(): void
    {
        $this->getId                    = static fn(UserStoryIdentifier $story): int => $story->getId();
        $this->user_stories_searcher    = SearchChildrenOfFeatureStub::withSuccessiveIds([
            [self::FIRST_USER_STORY_ID, self::SECOND_USER_STORY_ID],
            [self::THIRD_USER_STORY_ID, self::FOURTH_USER_STORY_ID],
        ]);
        $this->planned_stories_searcher = SearchUserStoryPlannedInIterationStub::withSuccessiveIds([
            [self::FIRST_USER_STORY_ID, self::SECOND_USER_STORY_ID],
            [self::THIRD_USER_STORY_ID, self::FOURTH_USER_STORY_ID],
        ]);
    }

    private function getCollectionFromFeatures(): UserStoryIdentifierCollection
    {
        return UserStoryIdentifierCollection::fromFeatureCollection(
            $this->user_stories_searcher,
            VerifyUserStoryIsVisibleStub::withAlwaysVisibleUserStories(),
            FeatureIdentifierCollectionBuilder::buildWithIds(243, 731),
            UserIdentifierStub::buildGenericUser()
        );
    }

    public function testItBuildsFlatCollectionOfUserStoriesFromCollectionOfFeatures(): void
    {
        $user_stories   = $this->getCollectionFromFeatures()->getUserStories();
        $user_story_ids = array_map($this->getId, $user_stories);
        self::assertCount(4, $user_story_ids);
        self::assertContains(self::FIRST_USER_STORY_ID, $user_story_ids);
        self::assertContains(self::SECOND_USER_STORY_ID, $user_story_ids);
        self::assertContains(self::THIRD_USER_STORY_ID, $user_story_ids);
        self::assertContains(self::FOURTH_USER_STORY_ID, $user_story_ids);
    }

    public function testItBuildsEmptyCollectionWhenNoUserStoryInAnyOfTheFeatures(): void
    {
        $this->user_stories_searcher = SearchChildrenOfFeatureStub::withoutUserStories();
        self::assertCount(0, $this->getCollectionFromFeatures()->getUserStories());
    }

    private function getCollectionFromMirrorIterations(): UserStoryIdentifierCollection
    {
        return UserStoryIdentifierCollection::fromMirroredIterationCollection(
            $this->planned_stories_searcher,
            VerifyUserStoryIsVisibleStub::withAlwaysVisibleUserStories(),
            MirroredIterationIdentifierCollectionBuilder::withIds(551, 815),
            UserIdentifierStub::buildGenericUser()
        );
    }

    public function testItBuildsFlatCollectionOfUserStoriesFromCollectionOfMirrorIterations(): void
    {
        $user_stories   = $this->getCollectionFromMirrorIterations()->getUserStories();
        $user_story_ids = array_map($this->getId, $user_stories);
        self::assertCount(4, $user_story_ids);
        self::assertContains(self::FIRST_USER_STORY_ID, $user_story_ids);
        self::assertContains(self::SECOND_USER_STORY_ID, $user_story_ids);
        self::assertContains(self::THIRD_USER_STORY_ID, $user_story_ids);
        self::assertContains(self::FOURTH_USER_STORY_ID, $user_story_ids);
    }

    public function testItBuildsEmptyCollectionWhenNoUserStoryInAnyOfTheMirrorIterations(): void
    {
        $this->planned_stories_searcher = SearchUserStoryPlannedInIterationStub::withoutUserStory();
        self::assertCount(0, $this->getCollectionFromMirrorIterations()->getUserStories());
    }

    public function testDifferenceReturnsANewCollectionThatDoesNotContainAnyStoryFromComparisonCollection(): void
    {
        $base_collection       = UserStoryIdentifierCollectionBuilder::buildWithIds(
            self::FIRST_USER_STORY_ID,
            self::SECOND_USER_STORY_ID,
            self::THIRD_USER_STORY_ID,
            self::FOURTH_USER_STORY_ID
        );
        $comparison_collection = UserStoryIdentifierCollectionBuilder::buildWithIds(
            self::SECOND_USER_STORY_ID,
            self::FOURTH_USER_STORY_ID
        );

        $difference = $base_collection->difference($comparison_collection);
        $ids        = array_map($this->getId, $difference->getUserStories());
        self::assertNotSame($base_collection, $difference);
        self::assertCount(2, $ids);
        self::assertSame([0, 1], array_keys($ids));
        self::assertContains(self::FIRST_USER_STORY_ID, $ids);
        self::assertNotContains(self::SECOND_USER_STORY_ID, $ids);
        self::assertContains(self::THIRD_USER_STORY_ID, $ids);
        self::assertNotContains(self::FOURTH_USER_STORY_ID, $ids);
    }

    public function testDifferenceReturnsEmptyCollectionWhenComparisonCollectionIsEquivalentToBaseCollection(): void
    {
        $base_collection       = UserStoryIdentifierCollectionBuilder::buildWithIds(
            self::FIRST_USER_STORY_ID,
            self::SECOND_USER_STORY_ID
        );
        $comparison_collection = UserStoryIdentifierCollectionBuilder::buildWithIds(
            self::FIRST_USER_STORY_ID,
            self::SECOND_USER_STORY_ID
        );

        $difference = $base_collection->difference($comparison_collection);
        self::assertNotSame($base_collection, $difference);
        self::assertCount(0, $difference->getUserStories());
    }

    public function testDifferenceReturnsEmptyCollectionWhenBaseCollectionIsEmpty(): void
    {
        $base_collection       = UserStoryIdentifierCollectionBuilder::buildEmpty();
        $comparison_collection = UserStoryIdentifierCollectionBuilder::buildWithIds(
            self::FIRST_USER_STORY_ID,
            self::SECOND_USER_STORY_ID
        );

        $difference = $base_collection->difference($comparison_collection);
        self::assertNotSame($base_collection, $difference);
        self::assertCount(0, $difference->getUserStories());
    }

    public function testDifferenceReturnsEquivalentCollectionWhenComparisonCollectionIsEmpty(): void
    {
        $base_collection       = UserStoryIdentifierCollectionBuilder::buildWithIds(
            self::FIRST_USER_STORY_ID,
            self::SECOND_USER_STORY_ID
        );
        $comparison_collection = UserStoryIdentifierCollectionBuilder::buildEmpty();

        $difference = $base_collection->difference($comparison_collection);
        $ids        = array_map($this->getId, $difference->getUserStories());
        self::assertNotSame($base_collection, $difference);
        self::assertCount(2, $ids);
        self::assertContains(self::FIRST_USER_STORY_ID, $ids);
        self::assertContains(self::SECOND_USER_STORY_ID, $ids);
    }
}
