<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program;

use Planning;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class TeamPlanningProxyTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildAPlanning(): void
    {
        $project_id = 101;
        $planning   = new Planning(1, 'test', $project_id, 'backlog title', 'plan title', []);
        $project    = ProjectTestBuilder::aProject()->withId($project_id)->build();
        $tracker    = TrackerTestBuilder::aTracker()->withId(1)->withProject($project)->build();
        $planning->setPlanningTracker($tracker);

        $team_planning = TeamPlanningProxy::fromPlanning($planning);

        self::assertEquals($planning->getId(), $team_planning->getId());
        self::assertEquals($planning->getPlanningTracker()->getId(), $team_planning->getPlanningTracker()->getId());
        self::assertEquals($planning->getName(), $team_planning->getName());
        self::assertEquals($planning->getBacklogTrackersIds(), $team_planning->getPlannableTrackerIds());
    }
}
