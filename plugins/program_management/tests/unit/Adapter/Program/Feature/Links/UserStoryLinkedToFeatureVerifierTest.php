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

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\FeatureIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildPlanningStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;

final class UserStoryLinkedToFeatureVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ArtifactsLinkedToParentDao
     */
    private $feature_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tracker_ArtifactFactory
     */
    private $artifact_factory;
    private UserIdentifier $user_identifier;
    private BuildPlanningStub $planning_builder;

    protected function setUp(): void
    {
        $this->planning_builder = BuildPlanningStub::withValidRootPlanning();
        $this->feature_dao      = $this->createMock(ArtifactsLinkedToParentDao::class);
        $this->artifact_factory = $this->createMock(\Tracker_ArtifactFactory::class);
        $this->user_identifier  = UserIdentifierStub::buildGenericUser();
    }

    private function getVerifier(): UserStoryLinkedToFeatureVerifier
    {
        return new UserStoryLinkedToFeatureVerifier(
            $this->feature_dao,
            $this->planning_builder,
            $this->artifact_factory,
            RetrieveUserStub::withGenericUser()
        );
    }

    public function testHasNotAPlannedUserStoryIfNoUserStoryIsLinked(): void
    {
        $this->feature_dao
            ->expects(self::once())
            ->method('getPlannedUserStory')
            ->willReturn([]);
        self::assertFalse(
            $this->getVerifier()->isLinkedToAtLeastOnePlannedUserStory($this->user_identifier, $this->buildFeature(101))
        );
    }

    public function testHasAPlannedUserStory(): void
    {
        $user_story_666 = ['project_id' => 666, 'user_story_id' => 666];
        $user_story_236 = ['project_id' => 25, 'user_story_id' => 236];

        $this->feature_dao
            ->expects(self::once())
            ->method('getPlannedUserStory')
            ->with(101)
            ->willReturn([$user_story_666, $user_story_236]);

        $this->feature_dao
            ->expects(self::exactly(2))
            ->method('isLinkedToASprintInMirroredProgramIncrement')
            ->withConsecutive([666, 20, 666], [236, 20, 25])
            ->willReturnOnConsecutiveCalls(false, true);

        self::assertTrue(
            $this->getVerifier()->isLinkedToAtLeastOnePlannedUserStory($this->user_identifier, $this->buildFeature(101))
        );
    }

    public function testHasNotAPlannedUserStoryIfUserStoriesAreLinkedButNotPlanned(): void
    {
        $user_story_666 = ['project_id' => 666, 'user_story_id' => 666];

        $this->feature_dao
            ->expects(self::once())
            ->method('getPlannedUserStory')
            ->with(101)
            ->willReturn([$user_story_666]);

        $this->feature_dao
            ->expects(self::once())
            ->method('isLinkedToASprintInMirroredProgramIncrement')
            ->with(666, 20, 666)
            ->willReturn(false);

        self::assertFalse(
            $this->getVerifier()->isLinkedToAtLeastOnePlannedUserStory($this->user_identifier, $this->buildFeature(101))
        );
    }

    public function testHasNotALinkedUserStoryToFeature(): void
    {
        $this->feature_dao
            ->expects(self::once())
            ->method('getChildrenOfFeatureInTeamProjects')
            ->willReturn([]);

        self::assertFalse(
            $this->getVerifier()->hasStoryLinked($this->user_identifier, $this->buildFeature(101))
        );
    }

    public function testHasNotALinkedUserStoryToFeatureThatUserCanSee(): void
    {
        $user_story = ['children_id' => 666];
        $this->feature_dao
            ->expects(self::once())
            ->method('getChildrenOfFeatureInTeamProjects')
            ->with(101)
            ->willReturn([$user_story]);

        $this->artifact_factory
            ->expects(self::once())
            ->method('getArtifactByIdUserCanView')
            ->with(self::isInstanceOf(\PFUser::class), 666)
            ->willReturn(null);

        self::assertFalse(
            $this->getVerifier()->hasStoryLinked($this->user_identifier, $this->buildFeature(101))
        );
    }

    public function testHasALinkedUserStoryToFeature(): void
    {
        $user_story = ['children_id' => 236];
        $this->feature_dao
            ->expects(self::once())
            ->method('getChildrenOfFeatureInTeamProjects')
            ->with(101)
            ->willReturn([$user_story]);

        $this->artifact_factory
            ->expects(self::once())
            ->method('getArtifactByIdUserCanView')
            ->with(self::isInstanceOf(\PFUser::class), 236)
            ->willReturn($this->createMock(\Artifact::class));

        self::assertTrue(
            $this->getVerifier()->hasStoryLinked($this->user_identifier, $this->buildFeature(101))
        );
    }

    public function testReturnFalseWhenUserHasAccessedToTheUserStory(): void
    {
        $user_story = ['project_id' => 101, 'user_story_id' => 666];
        $this->feature_dao
            ->expects(self::once())
            ->method('getPlannedUserStory')
            ->with(101)
            ->willReturn([$user_story]);

        $this->planning_builder = BuildPlanningStub::withoutRootValid();

        self::assertFalse(
            $this->getVerifier()->isLinkedToAtLeastOnePlannedUserStory($this->user_identifier, $this->buildFeature(101))
        );
    }

    private function buildFeature(int $feature_id): FeatureIdentifier
    {
        return FeatureIdentifierBuilder::build($feature_id, 110);
    }
}
