<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Planning;
use PlanningFactory;
use PlanningPermissionsManager;
use Tracker_Hierarchy;
use TrackerFactory;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PlanningFactoryTestGetPlanningTest extends TestCase
{
    private Planning $sprint_planning;
    private Planning $release_planning;
    private Tracker&MockObject $sprint_tracker;
    private Tracker&MockObject $release_tracker;
    private PFUser $user;
    private Tracker $backlog_tracker;
    private Tracker $planning_tracker;
    private PlanningFactory $planning_factory;
    private TrackerFactory&MockObject $tracker_factory;
    private PlanningDao&MockObject $planning_dao;

    #[\Override]
    protected function setUp(): void
    {
        $this->planning_dao           = $this->createMock(PlanningDao::class);
        $this->tracker_factory        = $this->createMock(TrackerFactory::class);
        $planning_permissions_manager = $this->createMock(PlanningPermissionsManager::class);

        $this->planning_factory = new PlanningFactory(
            $this->planning_dao,
            $this->tracker_factory,
            $planning_permissions_manager
        );

        $this->planning_dao->method('searchByMilestoneTrackerId')->willReturn([
            'id'                  => 1,
            'name'                => 'Release Planning',
            'group_id'            => 102,
            'planning_tracker_id' => 103,
            'backlog_title'       => 'Release Backlog',
            'plan_title'          => 'Sprint Plan',
        ]);

        $this->user = UserTestBuilder::buildWithDefaults();

        $this->planning_tracker = TrackerTestBuilder::aTracker()->withId(103)->withUserCanView(true)->build();
        $this->backlog_tracker  = TrackerTestBuilder::aTracker()->withId(104)->withUserCanView(true)->build();

        $epic_tracker          = TrackerTestBuilder::aTracker()->withId(101)->withUserCanView(true)->build();
        $story_tracker         = TrackerTestBuilder::aTracker()->withId(100)->withUserCanView(true)->build();
        $this->release_tracker = $this->mockTrackerWithId(107);
        $this->sprint_tracker  = $this->mockTrackerWithId(108);

        $this->tracker_factory->method('getTrackerById')
            ->willReturnCallback(fn(int $tracker_id) => match ($tracker_id) {
                100     => $story_tracker,
                101     => $epic_tracker,
                103     => $this->planning_tracker,
                104     => $this->backlog_tracker,
                107     => $this->release_tracker,
                108     => $this->sprint_tracker,
                default => null,
            });


        $this->tracker_factory->method('setCachedInstances')->willReturn([
            100 => $story_tracker,
            101 => $epic_tracker,
            107 => $this->release_tracker,
            108 => $this->sprint_tracker,
        ]);

        $this->createHierarchy();

        $this->planning_dao->method('searchByProjectId')
            ->willReturnCallback(static fn(int $project_id) => match ($project_id) {
                101     => [
                    [
                        'id'                  => 1,
                        'name'                => 'Sprint Planning',
                        'group_id'            => 123,
                        'planning_tracker_id' => 108,
                        'backlog_title'       => 'Release Backlog',
                        'plan_title'          => 'Sprint Plan',
                    ],
                    [
                        'id'                  => 2,
                        'name'                => 'Release Planning',
                        'group_id'            => 123,
                        'planning_tracker_id' => 107,
                        'backlog_title'       => 'Product Backlog',
                        'plan_title'          => 'Release Plan',
                    ],
                ],
                default => [],
            });

        $this->release_planning = PlanningBuilder::aPlanning(123)
            ->withId(2)
            ->withName('Release Planning')
            ->withBacklogTitle('Product Backlog')
            ->withPlanTitle('Release Plan')
            ->withBacklogTrackers($epic_tracker)
            ->withMilestoneTracker($this->release_tracker)
            ->build();
        $this->sprint_planning  = PlanningBuilder::aPlanning(123)
            ->withId(1)
            ->withName('Sprint Planning')
            ->withBacklogTitle('Release Backlog')
            ->withPlanTitle('Sprint Plan')
            ->withBacklogTrackers($this->backlog_tracker)
            ->withMilestoneTracker($this->sprint_tracker)
            ->build();
    }

    public function testItCanRetrieveBothAPlanningAndItsTrackers(): void
    {
        $group_id    = 42;
        $planning_id = 17;

        $planning_rows = [
            'id'                  => $planning_id,
            'name'                => 'Foo',
            'group_id'            => $group_id,
            'planning_tracker_id' => 103,
            'backlog_title'       => 'Release Backlog',
            'plan_title'          => 'Sprint Plan',
        ];

        $this->planning_dao->method('searchById')->with($planning_id)->willReturn($planning_rows);

        $this->planning_dao->method('searchBacklogTrackersByPlanningId')
            ->with($planning_id)
            ->willReturn([['tracker_id' => 104]]);

        $planning = $this->planning_factory->getPlanning($this->user, $planning_id);

        self::assertInstanceOf(Planning::class, $planning);
        self::assertEquals($this->planning_tracker, $planning->getPlanningTracker());
        self::assertEquals([$this->backlog_tracker], $planning->getBacklogTrackers());
    }

    public function testItReturnsAnEmptyArrayIfThereIsNoPlanningDefinedForAProject(): void
    {
        self::assertEquals([], $this->planning_factory->getPlannings($this->user, 102));
    }

    public function testItReturnsAllDefinedPlanningsForAProjectInTheOrderDefinedByTheHierarchy(): void
    {
        $matcher = $this->exactly(2);
        $this->planning_dao->expects($matcher)->method('searchBacklogTrackersByPlanningId')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame(1, $parameters[0]);
                return [['tracker_id' => 104]];
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame(2, $parameters[0]);
                return [['tracker_id' => 101]];
            }
        });


        $this->release_tracker->method('userCanView')->with($this->user)->willReturn(true);
        $this->sprint_tracker->method('userCanView')->with($this->user)->willReturn(true);

        self::assertEquals(
            [$this->release_planning, $this->sprint_planning],
            $this->planning_factory->getPlannings($this->user, 101)
        );
    }

    public function testItReturnsOnlyPlanningsWhereTheUserCanViewTrackers(): void
    {
        $this->planning_dao->method('searchBacklogTrackersByPlanningId')
            ->willReturnMap([
                [1, [['tracker_id' => 104]]],
                [2, [['tracker_id' => 101]]],
            ]);


        $this->release_tracker->method('userCanView')->with($this->user)->willReturn(false);
        $this->sprint_tracker->method('userCanView')->with($this->user)->willReturn(true);

        $this->assertEquals(
            [$this->sprint_planning],
            $this->planning_factory->getPlannings($this->user, 101)
        );
    }

    private function mockTrackerWithId(int $tracker_id): Tracker&MockObject
    {
        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn($tracker_id);

        return $tracker;
    }

    /**
     * backlog_tracker
     *    |_ planning_tracker
     *
     *  $this->release_tracker &&  $this->sprint_tracker can be added in both planning
     */
    private function createHierarchy(): void
    {
        $hierarchy = new Tracker_Hierarchy();

        $this->tracker_factory->method('getHierarchy')->willReturn($hierarchy);
        $hierarchy->addRelationship(104, 107);
        $hierarchy->addRelationship(104, 108);

        $hierarchy->addRelationship(103, 107);
        $hierarchy->addRelationship(103, 108);

        $hierarchy->addRelationship(107, 108);
    }
}
