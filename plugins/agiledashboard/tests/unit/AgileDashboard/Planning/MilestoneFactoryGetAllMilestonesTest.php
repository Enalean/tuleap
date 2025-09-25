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
use ArtifactNode;
use PFUser;
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
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MilestoneFactoryGetAllMilestonesTest extends TestCase
{
    private Project $project;
    private Tracker_ArtifactFactory&MockObject $artifact_factory;
    private PlanningFactory&MockObject $planning_factory;
    private Planning $planning;
    private PFUser $user;

    #[\Override]
    protected function setUp(): void
    {
        $this->user       = UserTestBuilder::buildWithDefaults();
        $this->project    = ProjectTestBuilder::aProject()->build();
        $planning_tracker = TrackerTestBuilder::aTracker()->withId(12)->withProject($this->project)->build();
        $this->planning   = PlanningBuilder::aPlanning((int) $this->project->getID())
            ->withId(1)
            ->withMilestoneTracker($planning_tracker)
            ->build();

        $this->planning_factory = $this->createMock(PlanningFactory::class);
        $this->artifact_factory = $this->createMock(Tracker_ArtifactFactory::class);
    }

    public function testItReturnsAnEmptyArrayWhenAllItemsAreClosed(): void
    {
        $this->artifact_factory->method('getArtifactsByTrackerIdUserCanView')->willReturn([]);

        self::assertEquals([], $this->newMileStoneFactory()->getAllMilestones($this->user, $this->planning));
    }

    public function testItReturnsAsManyMilestonesAsThereAreArtifacts(): void
    {
        $artifact1 = ArtifactTestBuilder::anArtifact(1)
            ->withChangesets(ChangesetTestBuilder::aChangeset(1)->build())
            ->build();
        $artifact2 = ArtifactTestBuilder::anArtifact(2)
            ->withChangesets(ChangesetTestBuilder::aChangeset(2)->build())
            ->build();

        $factory = $this->newMileStoneFactory();
        $factory->method('getPlannedArtifacts');
        $this->artifact_factory->method('getArtifactsByTrackerIdUserCanView')->willReturn([$artifact1, $artifact2]);
        self::assertCount(2, $factory->getAllMilestones($this->user, $this->planning));
    }

    public function testItReturnsMilestones(): void
    {
        $changeset01 = ChangesetTestBuilder::aChangeset(1)->build();
        $artifact    = $this->createMock(Artifact::class);

        $artifact->method('getId')->willReturn(101);
        $artifact->method('getLinkedArtifacts')->with($this->user)->willReturn([]);
        $artifact->method('getUniqueLinkedArtifacts')->with($this->user)->willReturn(null);
        $artifact->method('getLastChangeset')->willReturn($changeset01);

        $this->artifact_factory->method('getArtifactsByTrackerIdUserCanView')->willReturn([$artifact]);

        $factory = $this->newMileStoneFactory();
        $factory->method('getPlannedArtifacts');

        $all_milestones = $factory->getAllMilestones($this->user, $this->planning);
        self::assertEquals($artifact->getId(), $all_milestones[0]->getArtifact()->getId());
    }

    public function testItReturnsMilestonesWithPlannedArtifacts(): void
    {
        $artifact = $this->getAnArtifact();
        $planning = $this->getAPlanning();

        $this->artifact_factory->method('getArtifactsByTrackerIdUserCanView')->willReturn([$artifact]);

        $planned_artifacts = new ArtifactNode($artifact);
        $factory           = $this->newMileStoneFactory();
        $factory->method('getPlannedArtifacts')->willReturn($planned_artifacts);

        $milestone  = new Planning_ArtifactMilestone(
            $this->project,
            $planning,
            $artifact,
            $planned_artifacts
        );
        $milestones = $factory->getAllMilestones($this->user, $planning);
        self::assertEquals($milestone, $milestones[0]);
    }

    public function testItReturnsMilestonesWithoutPlannedArtifacts(): void
    {
        $artifact = $this->getAnArtifact();
        $planning = $this->getAPlanning();

        $this->artifact_factory->method('getArtifactsByTrackerIdUserCanView')->willReturn([$artifact]);

        $planned_artifacts = new ArtifactNode($artifact);
        $factory           = $this->newMileStoneFactory();
        $factory->method('getPlannedArtifacts')->willReturn($planned_artifacts);

        $milestone  = new Planning_ArtifactMilestone(
            $this->project,
            $planning,
            $artifact,
            null
        );
        $milestones = $factory->getAllMilestonesWithoutPlannedElement($this->user, $planning);
        self::assertEquals($milestone, $milestones[0]);
    }

    private function newMileStoneFactory(): Planning_MilestoneFactory&MockObject
    {
        return $this->getMockBuilder(Planning_MilestoneFactory::class)
            ->setConstructorArgs([
                $this->planning_factory,
                $this->artifact_factory,
                $this->createMock(Tracker_FormElementFactory::class),
                $this->createMock(AgileDashboard_Milestone_MilestoneStatusCounter::class),
                $this->createMock(PlanningPermissionsManager::class),
                $this->createMock(MilestoneDao::class),
                $this->createMock(SemanticTimeframeBuilder::class),
                new NullLogger(),
            ])
            ->onlyMethods(['getPlannedArtifacts'])
            ->getMock();
    }

    protected function getAnArtifact(): Artifact
    {
        return ArtifactTestBuilder::anArtifact(1)
            ->withChangesets(ChangesetTestBuilder::aChangeset(1)->build())
            ->build();
    }

    protected function getAPlanning(): Planning
    {
        return PlanningBuilder::aPlanning(101)
            ->withId(109)
            ->withMilestoneTracker(
                TrackerTestBuilder::aTracker()
                    ->withProject($this->project)
                    ->withId(7777777)
                    ->build()
            )
            ->build();
    }
}
