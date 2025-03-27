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
use PlanningFactory;
use Tracker;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PlanningTrackerBacklogCheckerTest extends TestCase
{
    private PlanningTrackerBacklogChecker $checker;
    private PlanningFactory&MockObject $planning_factory;
    private Tracker $tracker;
    private PFUser $user;

    protected function setUp(): void
    {
        $this->planning_factory = $this->createMock(PlanningFactory::class);
        $this->checker          = new PlanningTrackerBacklogChecker($this->planning_factory);
        $this->tracker          = TrackerTestBuilder::aTracker()
            ->withId(187)
            ->withProject(ProjectTestBuilder::aProject()->withId(104)->build())
            ->build();
        $this->user             = UserTestBuilder::buildWithDefaults();
    }

    public function testItReturnsFalseIfNoRootPlanningInProject(): void
    {
        $this->planning_factory->expects($this->once())->method('getRootPlanning')->willReturn(false);

        self::assertFalse($this->checker->isTrackerBacklogOfProjectRootPlanning(
            $this->tracker,
            $this->user
        ));
    }

    public function testItReturnsFalseIfNotARootBacklogTrackerPlanning(): void
    {
        $planning = PlanningBuilder::aPlanning(104)
            ->withBacklogTrackers(
                TrackerTestBuilder::aTracker()->withId(188)->build(),
                TrackerTestBuilder::aTracker()->withId(189)->build(),
            )
            ->build();

        $this->planning_factory->expects($this->once())->method('getRootPlanning')->willReturn($planning);

        self::assertFalse($this->checker->isTrackerBacklogOfProjectRootPlanning(
            $this->tracker,
            $this->user
        ));
    }

    public function testItReturnsTrueIfTrackerIsARootBacklogTrackerPlanning(): void
    {
        $planning = PlanningBuilder::aPlanning(104)
            ->withBacklogTrackers(
                TrackerTestBuilder::aTracker()->withId(188)->build(),
                $this->tracker,
            )
            ->build();

        $this->planning_factory->expects($this->once())->method('getRootPlanning')->willReturn($planning);

        self::assertTrue($this->checker->isTrackerBacklogOfProjectRootPlanning(
            $this->tracker,
            $this->user
        ));
    }

    public function testItReturnsFalseIfTrackerIsABacklogTrackerPlanning(): void
    {
        $planning = PlanningBuilder::aPlanning(104)
            ->withBacklogTrackers(
                TrackerTestBuilder::aTracker()->withId(188)->build(),
                TrackerTestBuilder::aTracker()->withId(189)->build(),
            )
            ->build();

        self::assertFalse($this->checker->isTrackerBacklogOfProjectPlanning(
            $planning,
            $this->tracker
        ));
    }

    public function testItReturnsTrueIfTrackerIsABacklogTrackerPlanning(): void
    {
        $planning = PlanningBuilder::aPlanning(104)
            ->withBacklogTrackers(
                TrackerTestBuilder::aTracker()->withId(188)->build(),
                $this->tracker,
            )
            ->build();

        self::assertTrue($this->checker->isTrackerBacklogOfProjectPlanning(
            $planning,
            $this->tracker
        ));
    }
}
