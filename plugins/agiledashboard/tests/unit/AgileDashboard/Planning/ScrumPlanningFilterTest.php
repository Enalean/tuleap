<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning;

use PHPUnit\Framework\MockObject\MockObject;
use PlanningFactory;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ScrumPlanningFilterTest extends TestCase
{
    private PlanningFactory&MockObject $planning_factory;
    private ScrumPlanningFilter $scrum_planning_filter;

    protected function setUp(): void
    {
        $this->planning_factory      = $this->createMock(PlanningFactory::class);
        $this->scrum_planning_filter = new ScrumPlanningFilter($this->planning_factory);
    }

    public function testItRetrievesMilestoneTracker(): void
    {
        $this->planning_factory->expects($this->once())->method('getAvailablePlanningTrackers');
        $tracker = TrackerTestBuilder::aTracker()->withId(888)->build();
        $this->planning_factory->method('getAvailablePlanningTrackers')->willReturn([$tracker]);

        $this->scrum_planning_filter->getPlanningTrackersFiltered(
            PlanningBuilder::aPlanning(1)->build(),
            UserTestBuilder::buildWithDefaults(),
            101
        );
    }
}
