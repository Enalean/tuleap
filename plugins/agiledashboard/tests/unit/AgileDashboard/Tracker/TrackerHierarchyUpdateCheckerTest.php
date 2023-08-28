<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Tracker;

use Planning;
use PlanningFactory;
use TrackerFactory;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class TrackerHierarchyUpdateCheckerTest extends TestCase
{
    private const PROJECT_ID         = 101;
    private const PARENT_TRACKER_ID  = 29;
    private const CHILD_TRACKER_ID   = 896;
    private const ANOTHER_TRACKER_ID = 820;

    private TrackerHierarchyUpdateChecker $checker;
    /**
     * @var PlanningFactory&\PHPUnit\Framework\MockObject\MockObject
     */
    private $planning_factory;
    /**
     * @var TrackerFactory&\PHPUnit\Framework\MockObject\MockObject
     */
    private $tracker_factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->planning_factory = $this->createMock(PlanningFactory::class);
        $this->tracker_factory  = $this->createMock(TrackerFactory::class);

        $this->checker = new TrackerHierarchyUpdateChecker(
            $this->planning_factory,
            $this->tracker_factory,
        );
    }

    public function testItDoesNotThrowExceptionIfThereIsNoPlannings(): void
    {
        $this->expectNotToPerformAssertions();

        $user           = UserTestBuilder::aUser()->build();
        $project        = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
        $parent_tracker = TrackerTestBuilder::aTracker()->withId(self::PARENT_TRACKER_ID)->withProject($project)->build();

        $this->planning_factory->method('getPlannings')->willReturn([]);

        $this->checker->canTrackersBeLinkedWithHierarchy(
            $user,
            $parent_tracker,
            [],
        );
    }

    public function testItDoesNotThrowExceptionIfChildrenListIsEmpty(): void
    {
        $this->expectNotToPerformAssertions();

        $user           = UserTestBuilder::aUser()->build();
        $project        = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
        $parent_tracker = TrackerTestBuilder::aTracker()->withId(self::PARENT_TRACKER_ID)->withProject($project)->build();

        $this->planning_factory->method('getPlannings')->willReturn($this->buildListOfPlannings([self::PARENT_TRACKER_ID]));

        $this->checker->canTrackersBeLinkedWithHierarchy(
            $user,
            $parent_tracker,
            [],
        );
    }

    public function testItDoesNotThrowExceptionIfOnlyOneParentTrackersIsInBacklogOfOnePlanning(): void
    {
        $this->expectNotToPerformAssertions();

        $user           = UserTestBuilder::aUser()->build();
        $project        = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
        $parent_tracker = TrackerTestBuilder::aTracker()->withId(self::PARENT_TRACKER_ID)->withProject($project)->build();

        $this->planning_factory->method('getPlannings')->willReturn($this->buildListOfPlannings([self::PARENT_TRACKER_ID]));

        $this->checker->canTrackersBeLinkedWithHierarchy(
            $user,
            $parent_tracker,
            [self::CHILD_TRACKER_ID],
        );
    }

    public function testItThrowsExceptionIfParentTrackersAndAtLeastOneChildTrackerAreInBacklogOfOnePlanning(): void
    {
        $user           = UserTestBuilder::aUser()->build();
        $project        = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
        $parent_tracker = TrackerTestBuilder::aTracker()->withId(self::PARENT_TRACKER_ID)->withProject($project)->build();

        $this->planning_factory
            ->method('getPlannings')
            ->willReturn(
                $this->buildListOfPlannings([self::PARENT_TRACKER_ID, self::CHILD_TRACKER_ID])
            );

        $this->tracker_factory
            ->method("getTrackerById")
            ->with(self::CHILD_TRACKER_ID)
            ->willReturn(
                TrackerTestBuilder::aTracker()->withId(self::CHILD_TRACKER_ID)->withProject($project)->build()
            );

        $this->expectException(TrackersCannotBeLinkedWithHierarchyException::class);

        $this->checker->canTrackersBeLinkedWithHierarchy(
            $user,
            $parent_tracker,
            [self::CHILD_TRACKER_ID],
        );
    }

    public function testItDoesNotThrowExceptionIfTrackersCanHaveHierarchicalLinks(): void
    {
        $this->expectNotToPerformAssertions();

        $user           = UserTestBuilder::aUser()->build();
        $project        = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
        $parent_tracker = TrackerTestBuilder::aTracker()->withId(self::PARENT_TRACKER_ID)->withProject($project)->build(
        );

        $this->planning_factory->method('getPlannings')->willReturn(
            $this->buildListOfPlannings([
                self::PARENT_TRACKER_ID,
                self::ANOTHER_TRACKER_ID,
            ])
        );

        $this->checker->canTrackersBeLinkedWithHierarchy(
            $user,
            $parent_tracker,
            [self::CHILD_TRACKER_ID],
        );
    }

    /**
     * @param int[] $scrum_backlog_ids
     *
     * @return Planning[]
     */
    private function buildListOfPlannings(array $scrum_backlog_ids): array
    {
        $backlog_trackers = array_map(
            fn(int $id) => TrackerTestBuilder::aTracker()->withId($id)->build(),
            $scrum_backlog_ids
        );
        return [
            PlanningBuilder::aPlanning(self::PROJECT_ID)->build(),
            PlanningBuilder::aPlanning(self::PROJECT_ID)->withBacklogTrackers(...$backlog_trackers)->build(),
        ];
    }
}
