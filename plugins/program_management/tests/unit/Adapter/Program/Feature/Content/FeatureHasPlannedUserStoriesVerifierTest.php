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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature\Content;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\SearchPlannedUserStory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\VerifyIsLinkedToAnotherMilestone;
use Tuleap\ProgramManagement\Tests\Builder\FeatureIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildPlanningStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchPlannedUserStoryStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsLinkedToAnotherMilestoneStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FeatureHasPlannedUserStoriesVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private SearchPlannedUserStory $search_planned_user_story;
    private BuildPlanningStub $planning_builder;
    private VerifyIsLinkedToAnotherMilestone $check_is_linked;

    #[\Override]
    protected function setUp(): void
    {
        $this->planning_builder          = BuildPlanningStub::withValidRootPlanning();
        $this->search_planned_user_story = SearchPlannedUserStoryStub::withoutUserStories();
        $this->check_is_linked           = VerifyIsLinkedToAnotherMilestoneStub::buildIsLinked();
    }

    private function isLinkedToAtLeastOnePlannedUserStory(): bool
    {
        $user    = UserIdentifierStub::buildGenericUser();
        $feature = FeatureIdentifierBuilder::withId(101);

        $verifier = new FeatureHasPlannedUserStoriesVerifier(
            $this->search_planned_user_story,
            $this->planning_builder,
            $this->check_is_linked
        );

        return $verifier->hasAtLeastOnePlannedUserStory($feature, $user);
    }

    public function testHasNotAPlannedUserStoryIfNoUserStoryIsLinked(): void
    {
        $this->search_planned_user_story = SearchPlannedUserStoryStub::withoutUserStories();
        self::assertFalse($this->isLinkedToAtLeastOnePlannedUserStory());
    }

    public function testHasAPlannedUserStory(): void
    {
        $user_story_666 = ['project_id' => 666, 'user_story_id' => 666];
        $user_story_236 = ['project_id' => 25, 'user_story_id' => 236];

        $this->search_planned_user_story = SearchPlannedUserStoryStub::withUserStories([
            $user_story_666,
            $user_story_236,
        ]);

        self::assertTrue($this->isLinkedToAtLeastOnePlannedUserStory());
    }

    public function testHasNotAPlannedUserStoryIfUserStoriesAreLinkedButNotPlanned(): void
    {
        $user_story_666 = ['project_id' => 666, 'user_story_id' => 666];

        $this->search_planned_user_story = SearchPlannedUserStoryStub::withUserStories([$user_story_666]);
        $this->check_is_linked           = VerifyIsLinkedToAnotherMilestoneStub::buildIsNotLinked();

        self::assertFalse($this->isLinkedToAtLeastOnePlannedUserStory());
    }

    public function testReturnFalseWhenUserHasAccessedToTheUserStory(): void
    {
        $this->planning_builder = BuildPlanningStub::withoutRootValid();

        self::assertFalse($this->isLinkedToAtLeastOnePlannedUserStory());
    }
}
