<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Program\Backlog\CreationCheck;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Planning;
use Project;
use Tuleap\ScaledAgile\Adapter\Program\PlanningAdapter;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\TopPlanningNotFoundInProjectException;
use Tuleap\ScaledAgile\Adapter\ProjectDataAdapter;
use Tuleap\ScaledAgile\Adapter\TrackerDataAdapter;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ArtifactCreatorCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\PlanningFactory
     */
    private $planning_factory;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProgramIncrementArtifactCreatorChecker
     */
    private $milestone_creator_checker;

    /**
     * @var ArtifactCreatorChecker
     */
    private $artifact_creator_checker;

    protected function setUp(): void
    {
        $this->planning_factory          = \Mockery::mock(\PlanningFactory::class);
        $planning_adapter                = new PlanningAdapter($this->planning_factory);
        $this->milestone_creator_checker = \Mockery::mock(ProgramIncrementArtifactCreatorChecker::class);

        $this->artifact_creator_checker = new ArtifactCreatorChecker(
            $planning_adapter,
            $this->milestone_creator_checker
        );
    }

    public function testDisallowArtifactCreationWhenItIsAMilestoneTrackerAndMilestoneCannotBeCreated(): void
    {
        $project  = new Project(['group_id' => 105, 'unix_group_name' => "project", "group_name" => "Project"]);
        $tracker  = TrackerTestBuilder::aTracker()->withId(1)->withProject($project)->build();
        $planning = new Planning(43, 'Planning', '', $project->getID(), '', [302, 504]);
        $planning->setPlanningTracker($tracker);
        $this->planning_factory->shouldReceive('getRootPlanning')->andReturn($planning);
        $this->milestone_creator_checker->shouldReceive('canProgramIncrementBeCreated')->andReturn(false);

        $this->assertFalse(
            $this->artifact_creator_checker->canCreateAnArtifact(
                UserTestBuilder::aUser()->build(),
                TrackerDataAdapter::build($tracker),
                ProjectDataAdapter::build($project)
            )
        );
    }

    public function testAllowArtifactCreationWhenNoVirtualTopMilestoneCanBeFound(): void
    {
        $project = new Project(['group_id' => 105, 'unix_group_name' => "project", "group_name" => "Project"]);
        $tracker = TrackerTestBuilder::aTracker()->withId(102)->withProject(\Project::buildForTest())->build();

        $this->planning_factory->shouldReceive('getRootPlanning')->andThrow(
            new TopPlanningNotFoundInProjectException($project->getID())
        );

        $this->assertTrue(
            $this->artifact_creator_checker->canCreateAnArtifact(
                UserTestBuilder::aUser()->build(),
                TrackerDataAdapter::build($tracker),
                ProjectDataAdapter::build($project)
            )
        );
    }

    public function testAllowArtifactCreationWhenTrackerDoesNotCreateMilestone(): void
    {
        $project  = new Project(['group_id' => 105, 'unix_group_name' => "project", "group_name" => "Project"]);
        $tracker  = TrackerTestBuilder::aTracker()->withId(102)->withProject($project)->build();
        $planning = new Planning(43, 'Planning', '', $project->getID(), '', [302, 504]);
        $planning->setPlanningTracker($tracker);

        $this->planning_factory->shouldReceive('getRootPlanning')->andReturn($planning);
        $this->milestone_creator_checker->shouldReceive('canProgramIncrementBeCreated')->andReturn(true);

        $this->assertTrue(
            $this->artifact_creator_checker->canCreateAnArtifact(
                UserTestBuilder::aUser()->build(),
                TrackerDataAdapter::build($tracker),
                ProjectDataAdapter::build($project)
            )
        );
    }
}
