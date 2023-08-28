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

use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class TeamPlanningProxyTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROJECT_ID              = 101;
    private const PLANNING_ID             = 43;
    private const PLANNING_NAME           = 'test';
    private const MILESTONE_TRACKER_ID    = 82;
    private const USER_STORIES_TRACKER_ID = 644;
    private const BUGS_TRACKER_ID         = 816;

    public function testItBuildAPlanning(): void
    {
        $project           = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
        $milestone_tracker = TrackerTestBuilder::aTracker()
            ->withId(self::MILESTONE_TRACKER_ID)
            ->withProject($project)
            ->build();
        $user_stories      = TrackerTestBuilder::aTracker()
            ->withId(self::USER_STORIES_TRACKER_ID)
            ->build();
        $bugs              = TrackerTestBuilder::aTracker()
            ->withId(self::BUGS_TRACKER_ID)
            ->build();

        $planning = PlanningBuilder::aPlanning(self::PROJECT_ID)
            ->withMilestoneTracker($milestone_tracker)
            ->withBacklogTrackers($user_stories, $bugs)
            ->withId(self::PLANNING_ID)
            ->withName(self::PLANNING_NAME)
            ->build();

        $team_planning = TeamPlanningProxy::fromPlanning($planning);

        self::assertSame(self::PLANNING_ID, $team_planning->getId());
        self::assertSame(self::MILESTONE_TRACKER_ID, $team_planning->getPlanningTracker()->getId());
        self::assertSame(self::PLANNING_NAME, $team_planning->getName());
        self::assertEqualsCanonicalizing(
            [self::USER_STORIES_TRACKER_ID, self::BUGS_TRACKER_ID],
            $team_planning->getPlannableTrackerIds()
        );
    }
}
