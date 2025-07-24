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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\SearchChildrenOfFeature;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\Content\SearchUserStoryPlannedInIteration;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MirroredIterationIdentifierCollection;
use Tuleap\ProgramManagement\Tests\Builder\FeatureIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\MirroredIterationIdentifierCollectionBuilder;
use Tuleap\ProgramManagement\Tests\Stub\SearchChildrenOfFeatureStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchUserStoryPlannedInIterationStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserStoryIsVisibleStub;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserStoryIdentifierTest extends TestCase
{
    private const FIRST_USER_STORY_ID  = 666;
    private const SECOND_USER_STORY_ID = 698;
    private const THIRD_USER_STORY_ID  = 714;
    private const FOURTH_USER_STORY_ID = 182;
    private SearchChildrenOfFeature $user_story_searcher;
    private VerifyUserStoryIsVisibleStub $verify_is_visible;
    private SearchUserStoryPlannedInIteration $search_user_story_planned_in_iteration;
    private \Closure $getId;
    private MirroredIterationIdentifierCollection $mirrored_iterations;

    #[\Override]
    protected function setUp(): void
    {
        $this->getId = static fn(UserStoryIdentifier $story): int => $story->getId();

        $this->verify_is_visible                      = VerifyUserStoryIsVisibleStub::withAlwaysVisibleUserStories();
        $this->user_story_searcher                    = SearchChildrenOfFeatureStub::withUserStoryIds(
            self::FIRST_USER_STORY_ID,
            self::SECOND_USER_STORY_ID
        );
        $this->search_user_story_planned_in_iteration = SearchUserStoryPlannedInIterationStub::withSuccessiveIds([
            [self::FIRST_USER_STORY_ID, self::SECOND_USER_STORY_ID],
            [self::THIRD_USER_STORY_ID, self::FOURTH_USER_STORY_ID],
        ]);

        $this->mirrored_iterations = MirroredIterationIdentifierCollectionBuilder::withIds(777, 821);
    }

    /**
     * @return UserStoryIdentifier[]
     */
    private function getCollectionFromFeature(): array
    {
        return UserStoryIdentifier::buildCollectionFromFeature(
            $this->user_story_searcher,
            $this->verify_is_visible,
            FeatureIdentifierBuilder::withId(1),
            UserIdentifierStub::buildGenericUser()
        );
    }

    public function testItBuildsUserStoryIdFromFeature(): void
    {
        $user_stories   = $this->getCollectionFromFeature();
        $user_story_ids = array_map($this->getId, $user_stories);
        self::assertCount(2, $user_story_ids);
        self::assertContains(self::FIRST_USER_STORY_ID, $user_story_ids);
        self::assertContains(self::SECOND_USER_STORY_ID, $user_story_ids);
    }

    public function testSkipsIfUserCanNotSeeFromFeature(): void
    {
        $this->verify_is_visible = VerifyUserStoryIsVisibleStub::withVisibleIds(self::SECOND_USER_STORY_ID);
        $user_stories            = $this->getCollectionFromFeature();
        $user_story_ids          = array_map($this->getId, $user_stories);
        self::assertNotContains(self::FIRST_USER_STORY_ID, $user_story_ids);
        self::assertContains(self::SECOND_USER_STORY_ID, $user_story_ids);
    }

    public function testItReturnsEmptyArrayWhenThereAreNoStoryInFeature(): void
    {
        $this->user_story_searcher = SearchChildrenOfFeatureStub::withoutUserStories();
        self::assertCount(0, $this->getCollectionFromFeature());
    }

    /**
     * @return UserStoryIdentifier[]
     */
    private function getCollectionFromMirroredIterations(): array
    {
        return UserStoryIdentifier::buildCollectionFromIteration(
            $this->search_user_story_planned_in_iteration,
            $this->verify_is_visible,
            $this->mirrored_iterations,
            UserIdentifierStub::buildGenericUser()
        );
    }

    public function testItBuildsUserStoryIdFromIteration(): void
    {
        $user_stories   = $this->getCollectionFromMirroredIterations();
        $user_story_ids = array_map($this->getId, $user_stories);
        self::assertCount(4, $user_story_ids);
        self::assertContains(self::FIRST_USER_STORY_ID, $user_story_ids);
        self::assertContains(self::SECOND_USER_STORY_ID, $user_story_ids);
        self::assertContains(self::THIRD_USER_STORY_ID, $user_story_ids);
        self::assertContains(self::FOURTH_USER_STORY_ID, $user_story_ids);
    }

    public function testSkipsIfUserCanNotSeeUserStoriesFromIteration(): void
    {
        $this->verify_is_visible = VerifyUserStoryIsVisibleStub::withVisibleIds(
            self::SECOND_USER_STORY_ID,
            self::FOURTH_USER_STORY_ID
        );
        $user_stories            = $this->getCollectionFromMirroredIterations();
        $user_story_ids          = array_map($this->getId, $user_stories);
        self::assertContains(self::SECOND_USER_STORY_ID, $user_story_ids);
        self::assertContains(self::FOURTH_USER_STORY_ID, $user_story_ids);
        self::assertNotContains(self::FIRST_USER_STORY_ID, $user_story_ids);
        self::assertNotContains(self::THIRD_USER_STORY_ID, $user_story_ids);
    }

    public function testItBuildsAnEmptyArrayIfNoMirrorIsFound(): void
    {
        $this->mirrored_iterations = MirroredIterationIdentifierCollectionBuilder::withoutIteration();
        self::assertCount(0, $this->getCollectionFromMirroredIterations());
    }

    public function testItBuildsAnEmptyArrayIfMirrorHasNoUserStory(): void
    {
        $this->search_user_story_planned_in_iteration = SearchUserStoryPlannedInIterationStub::withoutUserStory();
        self::assertCount(0, $this->getCollectionFromMirroredIterations());
    }

    public function testItBuildsAnEmptyArrayWhenUserCanNotSeeUserStory(): void
    {
        $this->verify_is_visible = VerifyUserStoryIsVisibleStub::withNoVisibleUserStory();
        self::assertCount(0, $this->getCollectionFromMirroredIterations());
    }
}
