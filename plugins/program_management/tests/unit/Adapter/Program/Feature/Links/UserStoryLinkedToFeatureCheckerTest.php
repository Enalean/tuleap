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
use PHPUnit\Framework\TestCase;
use Tuleap\ProgramManagement\Program\BuildPlanning;
use Tuleap\ProgramManagement\Program\PlanningConfiguration\Planning;
use Tuleap\ProgramManagement\ProgramTracker;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

class UserStoryLinkedToFeatureCheckerTest extends TestCase
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
     * @var UserStoryLinkedToFeatureChecker
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
            public function buildRootPlanning(\PFUser $user, int $project_id): Planning
            {
                return new Planning(
                    new ProgramTracker(TrackerTestBuilder::aTracker()->withId(20)->build()),
                    5,
                    'Release plan',
                    [50, 60],
                    new \Tuleap\ProgramManagement\Project($project_id, 'my-project', "My project")
                );
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
        self::assertFalse($this->checker->hasAPlannedUserStoryLinkedToFeature($this->user, 101));
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
            ->shouldReceive("isLinkedToASprintInMirroredMilestones")
            ->once()
            ->with(666, 20, 666)
            ->andReturn(false);
        $this->feature_dao
            ->shouldReceive("isLinkedToASprintInMirroredMilestones")
            ->once()
            ->with(236, 20, 25)
            ->andReturn(true);

        self::assertTrue($this->checker->hasAPlannedUserStoryLinkedToFeature($this->user, 101));
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
            ->shouldReceive("isLinkedToASprintInMirroredMilestones")
            ->once()
            ->with(666, 20, 666)
            ->andReturn(false);

        self::assertFalse($this->checker->hasAPlannedUserStoryLinkedToFeature($this->user, 101));
    }

    public function testHasNotALinkedUserStoryToFeature(): void
    {
        $this->feature_dao
            ->shouldReceive("getChildrenOfFeatureInTeamProjects")
            ->once()
            ->andReturn([]);

        self::assertFalse($this->checker->hasStoryLinked($this->user, 101));
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

        self::assertFalse($this->checker->hasStoryLinked($this->user, 101));
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

        self::assertTrue($this->checker->hasStoryLinked($this->user, 101));
    }
}
