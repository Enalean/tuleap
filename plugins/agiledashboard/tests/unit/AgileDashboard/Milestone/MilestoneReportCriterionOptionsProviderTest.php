<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone;

use AgileDashboard_Milestone_MilestoneReportCriterionOptionsProvider;
use AgileDashboard_Planning_NearestPlanningTrackerProvider;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use PlanningFactory;
use Tracker;
use Tracker_ArtifactFactory;
use Tracker_HierarchyFactory;
use TrackerFactory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MilestoneReportCriterionOptionsProviderTest extends TestCase
{
    private Tracker&MockObject $release_tracker;
    private Tracker&MockObject $sprint_tracker;
    private PFUser $user;
    private int $sprint_tracker_id;
    private Tracker $task_tracker;
    private Tracker_HierarchyFactory&MockObject $hierarchy_factory;
    private MilestoneDao&MockObject $dao;
    private AgileDashboard_Milestone_MilestoneReportCriterionOptionsProvider $provider;
    private AgileDashboard_Planning_NearestPlanningTrackerProvider&MockObject $nearest_planning_tracker_provider;

    protected function setUp(): void
    {
        /*
        Product          Epic
          Release  ----,-- Story
            Sprint ---'      Task
        */

        $this->user              = UserTestBuilder::buildWithDefaults();
        $release_tracker_id      = 101;
        $this->sprint_tracker_id = 1001;

        $project              = ProjectTestBuilder::aProject()->withId(111)->build();
        $this->task_tracker   = TrackerTestBuilder::aTracker()->withProject($project)->build();
        $this->sprint_tracker = $this->createMock(Tracker::class);
        $this->sprint_tracker->method('getId')->willReturn($this->sprint_tracker_id);
        $this->sprint_tracker->method('getGroupId')->willReturn(111);
        $this->release_tracker = $this->createMock(Tracker::class);
        $this->release_tracker->method('getId')->willReturn($release_tracker_id);
        $this->release_tracker->method('getGroupId')->willReturn(111);

        $planning_factory = $this->createMock(PlanningFactory::class);
        $planning_factory->method('getPlanningByPlanningTracker');
        $planning_factory->method('getVirtualTopPlanning');

        $this->hierarchy_factory = $this->createMock(Tracker_HierarchyFactory::class);
        $this->hierarchy_factory->method('getAllParents')->with($this->sprint_tracker)->willReturn([$this->release_tracker]);

        $tracker_factory = $this->createMock(TrackerFactory::class);
        $tracker_factory->method('getTrackerById')->with(1001)->willReturn($this->sprint_tracker);

        $artifact_factory = $this->createMock(Tracker_ArtifactFactory::class);

        $sprint_artifact_1231 = ArtifactTestBuilder::anArtifact(1231)
            ->userCannotView($this->user)
            ->build();
        $sprint_artifact_1232 = ArtifactTestBuilder::anArtifact(1232)
            ->userCanView($this->user)
            ->build();
        $sprint_artifact_1241 = ArtifactTestBuilder::anArtifact(1241)
            ->userCanView($this->user)
            ->build();
        $matcher              = $this->exactly(3);
        $artifact_factory->method('getArtifactById')
            ->willReturnMap([
                [1231, $sprint_artifact_1231],
                [1232, $sprint_artifact_1232],
                [1241, $sprint_artifact_1241],
            ]);

        $this->dao                               = $this->createMock(MilestoneDao::class);
        $this->nearest_planning_tracker_provider = $this->createMock(AgileDashboard_Planning_NearestPlanningTrackerProvider::class);

        $this->provider = new AgileDashboard_Milestone_MilestoneReportCriterionOptionsProvider(
            $this->nearest_planning_tracker_provider,
            $this->dao,
            $this->hierarchy_factory,
            $planning_factory,
            $tracker_factory,
            $artifact_factory
        );
    }

    public function testItReturnsEmptyArrayWhenNoNearestPlanningTracker(): void
    {
        $this->nearest_planning_tracker_provider->method('getNearestPlanningTracker')
            ->with($this->user, $this->task_tracker, $this->hierarchy_factory)
            ->willReturn(null);

        $this->dao->expects(self::never())->method('getAllMilestoneByTrackers');

        self::assertEmpty($this->provider->getSelectboxOptions($this->task_tracker, 0, $this->user));
    }

    public function testItDoesNotSearchOnProductTrackerSinceThereIsNoPlanning(): void
    {
        $this->nearest_planning_tracker_provider->method('getNearestPlanningTracker')
            ->with($this->user, $this->task_tracker, $this->hierarchy_factory)->willReturn($this->sprint_tracker);

        $this->release_tracker->method('userCanView')->with($this->user)->willReturn(true);
        $this->sprint_tracker->method('userCanView')->with($this->user)->willReturn(true);

        $this->dao->expects(self::atLeastOnce())->method('getAllMilestoneByTrackers')
            ->with([$this->sprint_tracker_id])
            ->willReturn($this->getDBResults());

        $this->provider->getSelectboxOptions($this->task_tracker, 0, $this->user);
    }

    public function testItDoesNotSearchOnMilestonesUserCantView(): void
    {
        $this->nearest_planning_tracker_provider->method('getNearestPlanningTracker')
            ->with($this->user, $this->task_tracker, $this->hierarchy_factory)
            ->willReturn($this->sprint_tracker);

        $this->release_tracker->method('userCanView')->with($this->user)->willReturn(false);
        $this->sprint_tracker->method('userCanView')->with($this->user)->willReturn(true);

        $this->dao->expects(self::once())->method('getAllMilestoneByTrackers')
            ->willReturn($this->getDBResults());

        $options = $this->provider->getSelectboxOptions($this->task_tracker, 0, $this->user);

        self::assertDoesNotMatchRegularExpression('/Sprint 31/', implode('', $options));
    }

    private function getDBResults(): array
    {
        return [
            [
                'm101_id'     => '123',
                'm101_title'  => 'Tuleap 6.5',
                'm1001_id'    => '1231',
                'm1001_title' => 'Sprint 31',
            ],
            [
                'm101_id'     => '123',
                'm101_title'  => 'Tuleap 6.5',
                'm1001_id'    => '1232',
                'm1001_title' => 'Sprint 32',
            ],
            [
                'm101_id'     => '124',
                'm101_title'  => 'Tuleap 6.6',
                'm1001_id'    => '1241',
                'm1001_title' => 'Sprint 33',
            ],
        ];
    }
}
