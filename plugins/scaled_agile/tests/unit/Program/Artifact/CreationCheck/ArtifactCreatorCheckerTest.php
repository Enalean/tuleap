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
use Tuleap\ScaledAgile\Program\PlanningConfiguration\PlanningAdapter;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\PlanningData;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\TopPlanningNotFoundInProjectException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ArtifactCreatorCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PlanningAdapter
     */
    private $planning_adapter;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProjectIncrementArtifactCreatorChecker
     */
    private $milestone_creator_checker;

    /**
     * @var ArtifactCreatorChecker
     */
    private $artifact_creator_checker;

    protected function setUp(): void
    {
        $this->planning_adapter          = \Mockery::mock(PlanningAdapter::class);
        $this->milestone_creator_checker = \Mockery::mock(ProjectIncrementArtifactCreatorChecker::class);

        $this->artifact_creator_checker = new ArtifactCreatorChecker(
            $this->planning_adapter,
            $this->milestone_creator_checker
        );
    }

    public function testDisallowArtifactCreationWhenItIsAMilestoneTrackerAndMilestoneCannotBeCreated(): void
    {
        $project  = \Project::buildForTest();
        $tracker  = TrackerTestBuilder::aTracker()->withId(1)->withProject($project)->build();
        $planning = new PlanningData($tracker, 43, 'Planning', [302, 504]);
        $this->planning_adapter->shouldReceive('buildRootPlanning')->andReturn(
            $planning
        );
        $this->milestone_creator_checker->shouldReceive('canProjectIncrementBeCreated')->andReturn(false);

        $this->assertFalse(
            $this->artifact_creator_checker->canCreateAnArtifact(
                UserTestBuilder::aUser()->build(),
                $tracker
            )
        );
    }

    public function testAllowArtifactCreationWhenNoVirtualTopMilestoneCanBeFound(): void
    {
        $this->planning_adapter->shouldReceive('buildRootPlanning')->andThrow(
            new TopPlanningNotFoundInProjectException(102)
        );

        $tracker = TrackerTestBuilder::aTracker()->withId(102)->withProject(\Project::buildForTest())->build();

        $this->assertTrue(
            $this->artifact_creator_checker->canCreateAnArtifact(
                UserTestBuilder::aUser()->build(),
                $tracker
            )
        );
    }

    public function testAllowArtifactCreationWhenTrackerDoesNotCreateMilestone(): void
    {
        $project          = \Project::buildForTest();
        $planning_tracker = TrackerTestBuilder::aTracker()->withId(1)->withProject($project)->build();
        $planning         = new PlanningData($planning_tracker, 43, 'Planning', [302, 504]);
        $this->planning_adapter->shouldReceive('buildRootPlanning')->andReturn($planning);
        $tracker = TrackerTestBuilder::aTracker()->withId(102)->withProject($project)->build();

        $this->assertTrue(
            $this->artifact_creator_checker->canCreateAnArtifact(
                UserTestBuilder::aUser()->build(),
                $tracker
            )
        );
    }
}
