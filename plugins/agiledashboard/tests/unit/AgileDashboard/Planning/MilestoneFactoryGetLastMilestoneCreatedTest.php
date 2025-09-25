<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning;

use AgileDashboard_Milestone_MilestoneStatusCounter;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Planning_ArtifactMilestone;
use Planning_MilestoneFactory;
use Planning_NoMilestone;
use PlanningFactory;
use PlanningPermissionsManager;
use Psr\Log\NullLogger;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tuleap\AgileDashboard\Milestone\MilestoneDao;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MilestoneFactoryGetLastMilestoneCreatedTest extends TestCase
{
    private int $planning_tracker_id;
    private int $planning_id;
    private PFUser $current_user;
    private Planning_MilestoneFactory&MockObject $milestone_factory;
    private Artifact $sprint_1_artifact;
    private Planning_ArtifactMilestone&MockObject $sprint_1_milestone;
    private Tracker_ArtifactFactory&MockObject $artifact_factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->current_user      = UserTestBuilder::buildWithDefaults();
        $planning_factory        = $this->createMock(PlanningFactory::class);
        $this->artifact_factory  = $this->createMock(Tracker_ArtifactFactory::class);
        $this->milestone_factory = $this->getMockBuilder(Planning_MilestoneFactory::class)
            ->setConstructorArgs([
                $planning_factory,
                $this->artifact_factory,
                $this->createMock(Tracker_FormElementFactory::class),
                $this->createMock(AgileDashboard_Milestone_MilestoneStatusCounter::class),
                $this->createMock(PlanningPermissionsManager::class),
                $this->createMock(MilestoneDao::class),
                $this->createMock(SemanticTimeframeBuilder::class),
                new NullLogger(),
            ])
            ->onlyMethods(['getMilestoneFromArtifact'])
            ->getMock();

        $this->sprint_1_artifact  = ArtifactTestBuilder::anArtifact(1)->build();
        $this->sprint_1_milestone = $this->createMock(Planning_ArtifactMilestone::class);

        $this->planning_id         = 12;
        $this->planning_tracker_id = 123;
        $planning_tracker          = TrackerTestBuilder::aTracker()
            ->withId($this->planning_tracker_id)
            ->withProject(ProjectTestBuilder::aProject()->build())
            ->build();

        $planning = PlanningBuilder::aPlanning(101)
            ->withId($this->planning_id)
            ->withMilestoneTracker($planning_tracker)
            ->build();

        $planning_factory->method('getPlanning')->with($this->current_user, $this->planning_id)->willReturn($planning);
    }

    public function testItReturnsEmptyMilestoneWhenNothingMatches(): void
    {
        $this->artifact_factory->method('getOpenArtifactsByTrackerIdUserCanView')->willReturn([]);
        $milestone = $this->milestone_factory->getLastMilestoneCreated($this->current_user, $this->planning_id);
        self::assertInstanceOf(Planning_NoMilestone::class, $milestone);
    }

    public function testItReturnsTheLastOpenArtifactOfPlanningTracker(): void
    {
        $this->artifact_factory->method('getOpenArtifactsByTrackerIdUserCanView')
            ->with($this->current_user, $this->planning_tracker_id)
            ->willReturn(['115' => $this->sprint_1_artifact, '104' => ArtifactTestBuilder::anArtifact(104)->build()]);

        $this->milestone_factory->method('getMilestoneFromArtifact')
            ->with($this->current_user, $this->sprint_1_artifact)
            ->willReturn($this->sprint_1_milestone);

        $milestone = $this->milestone_factory->getLastMilestoneCreated($this->current_user, $this->planning_id);
        self::assertEquals($this->sprint_1_milestone, $milestone);
    }
}
