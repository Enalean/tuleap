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
use PHPUnit\Framework\MockObject\MockObject;
use Planning;
use Planning_ArtifactMilestone;
use Planning_MilestoneFactory;
use PlanningFactory;
use PlanningPermissionsManager;
use Project;
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
final class MilestoneFactoryGetMilestoneFromArtifactTest extends TestCase
{
    private PlanningFactory&MockObject $planning_factory;
    private Planning_MilestoneFactory $milestone_factory;
    private Artifact $task_artifact;
    private Artifact $release_artifact;
    private Planning $release_planning;
    private Project $project;
    private \PFUser $user;

    protected function setUp(): void
    {
        $this->project          = ProjectTestBuilder::aProject()->build();
        $this->release_planning = PlanningBuilder::aPlanning((int) $this->project->getID())->build();

        $release_tracker        = TrackerTestBuilder::aTracker()->withProject($this->project)->build();
        $this->release_artifact = ArtifactTestBuilder::anArtifact(1)->inTracker($release_tracker)->build();

        $task_tracker        = TrackerTestBuilder::aTracker()->withProject($this->project)->build();
        $this->task_artifact = ArtifactTestBuilder::anArtifact(2)->inTracker($task_tracker)->build();

        $this->planning_factory = $this->createMock(PlanningFactory::class);

        $this->user = UserTestBuilder::buildWithDefaults();

        $this->milestone_factory = new Planning_MilestoneFactory(
            $this->planning_factory,
            $this->createMock(Tracker_ArtifactFactory::class),
            $this->createMock(Tracker_FormElementFactory::class),
            $this->createMock(AgileDashboard_Milestone_MilestoneStatusCounter::class),
            $this->createMock(PlanningPermissionsManager::class),
            $this->createMock(MilestoneDao::class),
            $this->createMock(SemanticTimeframeBuilder::class),
            new NullLogger(),
        );
    }

    public function testItCreateMilestoneFromArtifact(): void
    {
        $this->planning_factory->expects(self::once())->method('getPlanningByPlanningTracker')->willReturn($this->release_planning);
        $this->assertEqualToReleaseMilestone($this->milestone_factory->getMilestoneFromArtifact($this->user, $this->release_artifact));
    }

    private function assertEqualToReleaseMilestone($actual_release_milestone): void
    {
        $expected_release_milestone = new Planning_ArtifactMilestone(
            $this->project,
            $this->release_planning,
            $this->release_artifact,
        );
        self::assertEquals($expected_release_milestone, $actual_release_milestone);
    }

    public function testItReturnsNullWhenThereIsNoPlanningForTheTracker(): void
    {
        $this->planning_factory->expects(self::once())->method('getPlanningByPlanningTracker')->willReturn(null);
        self::assertNull($this->milestone_factory->getMilestoneFromArtifact($this->user, $this->task_artifact));
    }
}
