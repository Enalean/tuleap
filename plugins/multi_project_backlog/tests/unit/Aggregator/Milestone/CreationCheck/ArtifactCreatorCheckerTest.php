<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\MultiProjectBacklog\Aggregator\Milestone\CreationCheck;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Planning;
use Planning_NoPlanningsException;
use Planning_VirtualTopMilestone;
use Tuleap\Test\Builders\UserTestBuilder;

final class ArtifactCreatorCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Planning_MilestoneFactory
     */
    private $planning_milestone_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|MilestoneCreatorChecker
     */
    private $milestone_creator_checker;

    /**
     * @var ArtifactCreatorChecker
     */
    private $artifact_creator_checker;

    protected function setUp(): void
    {
        $this->planning_milestone_factory = \Mockery::mock(\Planning_MilestoneFactory::class);
        $this->milestone_creator_checker  = \Mockery::mock(MilestoneCreatorChecker::class);

        $this->artifact_creator_checker = new ArtifactCreatorChecker($this->planning_milestone_factory, $this->milestone_creator_checker);
    }

    public function testDisallowArtifactCreationWhenItIsAMilestoneTrackerAndMilestoneCannotBeCreated(): void
    {
        $project  = \Project::buildForTest();
        $planning = \Mockery::mock(Planning::class);
        $this->planning_milestone_factory->shouldReceive('getVirtualTopMilestone')->andReturn(
            new Planning_VirtualTopMilestone($project, $planning)
        );
        $planning->shouldReceive('getPlanningTrackerId')->andReturn(102);
        $tracker = \Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(102);
        $tracker->shouldReceive('getProject')->andReturn($project);
        $this->milestone_creator_checker->shouldReceive('canMilestoneBeCreated')->andReturn(false);

        $this->assertFalse(
            $this->artifact_creator_checker->canCreateAnArtifact(
                UserTestBuilder::aUser()->build(),
                $tracker
            )
        );
    }

    public function testAllowArtifactCreationWhenNoVirtualTopMilestoneCanBeFound(): void
    {
        $this->planning_milestone_factory->shouldReceive('getVirtualTopMilestone')->andThrow(new Planning_NoPlanningsException());

        $tracker = \Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getProject')->andReturn(\Project::buildForTest());

        $this->assertTrue(
            $this->artifact_creator_checker->canCreateAnArtifact(
                UserTestBuilder::aUser()->build(),
                $tracker
            )
        );
    }

    public function testAllowArtifactCreationWhenTrackerDoesNotCreateMilestone(): void
    {
        $project  = \Project::buildForTest();
        $planning = \Mockery::mock(Planning::class);
        $this->planning_milestone_factory->shouldReceive('getVirtualTopMilestone')->andReturn(
            new Planning_VirtualTopMilestone($project, $planning)
        );
        $planning->shouldReceive('getPlanningTrackerId')->andReturn(999);
        $tracker = \Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(102);
        $tracker->shouldReceive('getProject')->andReturn($project);

        $this->assertTrue(
            $this->artifact_creator_checker->canCreateAnArtifact(
                UserTestBuilder::aUser()->build(),
                $tracker
            )
        );
    }
}
