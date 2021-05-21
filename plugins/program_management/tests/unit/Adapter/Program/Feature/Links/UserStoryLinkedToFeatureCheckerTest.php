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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\Links\VerifyLinkedUserStoryIsNotPlanned;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\BuildPlanning;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Project;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\VerifyIsVisibleFeatureStub;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class UserStoryLinkedToFeatureCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ArtifactsLinkedToParentDao
     */
    private $feature_dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var VerifyLinkedUserStoryIsNotPlanned
     */
    private $checker;
    /**
     * @var \PFUser
     */
    private $user;

    protected function setUp(): void
    {
        $planning_builder = new class implements BuildPlanning
        {
            public function getRootPlanning(\PFUser $user, int $project_id): \Planning
            {
                $planning = new \Planning(1, "Root planning", $project_id, '', '');
                $planning->setPlanningTracker(TrackerTestBuilder::aTracker()->withId(20)->build());
                return $planning;
            }

            public function getProjectFromPlanning(\Planning $root_planning): Project
            {
                return new \Tuleap\ProgramManagement\Domain\Project(101, 'my-project', "My project");
            }
        };

        $this->feature_dao      = \Mockery::mock(ArtifactsLinkedToParentDao::class);
        $this->artifact_factory = \Mockery::mock(\Tracker_ArtifactFactory::class);
        $this->user             = new \PFUser(['language_id' => 'en']);
        $this->checker          = new UserStoryLinkedToFeatureChecker($this->feature_dao, $planning_builder, $this->artifact_factory);
    }

    public function testHasNotAPlannedUserStoryIfNoUserStoryIsLinked(): void
    {
        $this->feature_dao
            ->shouldReceive("getPlannedUserStory")
            ->once()
            ->andReturn([]);
        self::assertFalse($this->checker->isLinkedToAtLeastOnePlannedUserStory($this->user, $this->buildFeature(101)));
    }

    public function testHasAPlannedUserStory(): void
    {
        $user_story_666 = ['project_id' => 666, 'user_story_id' => 666];
        $user_story_236 = ['project_id' => 25, 'user_story_id' => 236];

        $this->feature_dao
            ->shouldReceive("getPlannedUserStory")
            ->once()
            ->with(101)
            ->andReturn([$user_story_666, $user_story_236]);

        $this->feature_dao
            ->shouldReceive("isLinkedToASprintInMirroredProgramIncrement")
            ->once()
            ->with(666, 20, 666)
            ->andReturn(false);
        $this->feature_dao
            ->shouldReceive("isLinkedToASprintInMirroredProgramIncrement")
            ->once()
            ->with(236, 20, 25)
            ->andReturn(true);

        self::assertTrue($this->checker->isLinkedToAtLeastOnePlannedUserStory($this->user, $this->buildFeature(101)));
    }

    public function testHasNotAPlannedUserStoryIfUserStoriesAreLinkedButNotPlanned(): void
    {
        $user_story_666 = ['project_id' => 666, 'user_story_id' => 666];

        $this->feature_dao
            ->shouldReceive("getPlannedUserStory")
            ->once()
            ->with(101)
            ->andReturn([$user_story_666]);

        $this->feature_dao
            ->shouldReceive("isLinkedToASprintInMirroredProgramIncrement")
            ->once()
            ->with(666, 20, 666)
            ->andReturn(false);

        self::assertFalse($this->checker->isLinkedToAtLeastOnePlannedUserStory($this->user, $this->buildFeature(101)));
    }

    public function testHasNotALinkedUserStoryToFeature(): void
    {
        $this->feature_dao
            ->shouldReceive("getChildrenOfFeatureInTeamProjects")
            ->once()
            ->andReturn([]);

        self::assertFalse($this->checker->hasStoryLinked($this->user, $this->buildFeature(101)));
    }

    public function testHasNotALinkedUserStoryToFeatureThatUserCanSee(): void
    {
        $user_story = ['children_id' => 666];
        $this->feature_dao
            ->shouldReceive("getChildrenOfFeatureInTeamProjects")
            ->once()
            ->with(101)
            ->andReturn([$user_story]);

        $this->artifact_factory
            ->shouldReceive('getArtifactByIdUserCanView')
            ->once()
            ->with($this->user, 666)
            ->andReturnNull();

        self::assertFalse($this->checker->hasStoryLinked($this->user, $this->buildFeature(101)));
    }

    public function testHasALinkedUserStoryToFeature(): void
    {
        $user_story = ['children_id' => 236];
        $this->feature_dao
            ->shouldReceive("getChildrenOfFeatureInTeamProjects")
            ->once()
            ->with(101)
            ->andReturn([$user_story]);

        $this->artifact_factory
            ->shouldReceive('getArtifactByIdUserCanView')
            ->once()
            ->with($this->user, 236)
            ->andReturn(\Mockery::mock(\Artifact::class));

        self::assertTrue($this->checker->hasStoryLinked($this->user, $this->buildFeature(101)));
    }

    private function buildFeature(int $feature_id): FeatureIdentifier
    {
        return FeatureIdentifier::fromId(VerifyIsVisibleFeatureStub::buildVisibleFeature(), $feature_id, $this->user, ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 110, $this->user));
    }
}
