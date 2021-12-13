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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature\Links;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\SearchChildrenOfFeature;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\SearchPlannedUserStory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\VerifyIsLinkedToAnotherMilestone;
use Tuleap\ProgramManagement\Tests\Builder\FeatureIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildPlanningStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchChildrenOfFeatureStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchPlannedUserStoryStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsLinkedToAnotherMilestoneStub;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

final class UserStoryLinkedToFeatureVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private SearchPlannedUserStory $search_planned_user_story;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tracker_ArtifactFactory
     */
    private $artifact_factory;
    private BuildPlanningStub $planning_builder;
    private SearchChildrenOfFeature $search_children_of_feature;
    private VerifyIsLinkedToAnotherMilestone $check_is_linked;

    protected function setUp(): void
    {
        $this->planning_builder           = BuildPlanningStub::withValidRootPlanning();
        $this->search_planned_user_story  = SearchPlannedUserStoryStub::withoutUserStories();
        $this->artifact_factory           = $this->createMock(\Tracker_ArtifactFactory::class);
        $this->search_children_of_feature = SearchChildrenOfFeatureStub::withoutChildren();
        $this->check_is_linked            = VerifyIsLinkedToAnotherMilestoneStub::buildIsLinked();
    }

    private function getVerifier(): UserStoryLinkedToFeatureVerifier
    {
        return new UserStoryLinkedToFeatureVerifier(
            $this->search_planned_user_story,
            $this->planning_builder,
            $this->artifact_factory,
            RetrieveUserStub::withGenericUser(),
            $this->search_children_of_feature,
            $this->check_is_linked
        );
    }

    private function isLinkedToAtLeastOnePlannedUserStory(): bool
    {
        $user    = UserIdentifierStub::buildGenericUser();
        $feature = FeatureIdentifierBuilder::withId(101);
        return $this->getVerifier()->isLinkedToAtLeastOnePlannedUserStory($user, $feature);
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

        $this->search_planned_user_story = SearchPlannedUserStoryStub::withUserStories(
            [$user_story_666, $user_story_236]
        );

        self::assertTrue($this->isLinkedToAtLeastOnePlannedUserStory());
    }

    public function testHasNotAPlannedUserStoryIfUserStoriesAreLinkedButNotPlanned(): void
    {
        $user_story_666 = ['project_id' => 666, 'user_story_id' => 666];

        $this->search_planned_user_story = SearchPlannedUserStoryStub::withUserStories([$user_story_666]);

        $this->search_children_of_feature = SearchChildrenOfFeatureStub::withoutChildren();
        $this->check_is_linked            = VerifyIsLinkedToAnotherMilestoneStub::buildIsNotLinked();

        self::assertFalse($this->isLinkedToAtLeastOnePlannedUserStory());
    }

    public function testReturnFalseWhenUserHasAccessedToTheUserStory(): void
    {
        $user_story = ['project_id' => 101, 'user_story_id' => 666];

        $this->search_children_of_feature = SearchChildrenOfFeatureStub::withChildren([$user_story]);
        $this->planning_builder           = BuildPlanningStub::withoutRootValid();

        self::assertFalse($this->isLinkedToAtLeastOnePlannedUserStory());
    }

    private function hasAtLeastOneStory(): bool
    {
        $user    = UserIdentifierStub::buildGenericUser();
        $feature = FeatureIdentifierBuilder::withId(101);
        return $this->getVerifier()->hasStoryLinked($user, $feature);
    }

    public function testHasNotALinkedUserStoryToFeature(): void
    {
        self::assertFalse($this->hasAtLeastOneStory());
    }

    public function testHasNotALinkedUserStoryToFeatureThatUserCanSee(): void
    {
        $user_story = ['children_id' => 666];

        $this->search_children_of_feature = SearchChildrenOfFeatureStub::withChildren([$user_story]);

        $this->artifact_factory
            ->expects(self::once())
            ->method('getArtifactByIdUserCanView')
            ->with(self::isInstanceOf(\PFUser::class), 666)
            ->willReturn(null);

        self::assertFalse($this->hasAtLeastOneStory());
    }

    public function testHasALinkedUserStoryToFeature(): void
    {
        $user_story                       = ['children_id' => 236];
        $this->search_children_of_feature = SearchChildrenOfFeatureStub::withChildren([$user_story]);

        $this->artifact_factory
            ->expects(self::once())
            ->method('getArtifactByIdUserCanView')
            ->with(self::isInstanceOf(\PFUser::class), 236)
            ->willReturn(ArtifactTestBuilder::anArtifact(964)->build());

        self::assertTrue($this->hasAtLeastOneStory());
    }
}
