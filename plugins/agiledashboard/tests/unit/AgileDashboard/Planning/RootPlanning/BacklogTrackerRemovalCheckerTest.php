<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning\RootPlanning;

use PlanningParameters;
use Tuleap\AgileDashboard\Planning\TrackerHaveAtLeastOneAddToTopBacklogPostActionException;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\AgileDashboard\Workflow\AddToTopBacklogPostActionDao;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class BacklogTrackerRemovalCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private AddToTopBacklogPostActionDao & \PHPUnit\Framework\MockObject\MockObject $add_to_top_backlog_post_action_dao;
    /** @var int[] */
    private array $backlog_tracker_ids;

    protected function setUp(): void
    {
        $this->add_to_top_backlog_post_action_dao = $this->createMock(AddToTopBacklogPostActionDao::class);

        $this->backlog_tracker_ids = [1];
    }

    private function check(): void
    {
        $checker = new BacklogTrackerRemovalChecker($this->add_to_top_backlog_post_action_dao);

        $first_tracker  = TrackerTestBuilder::aTracker()->withId(1)->build();
        $second_tracker = TrackerTestBuilder::aTracker()->withId(2)->build();
        $third_tracker  = TrackerTestBuilder::aTracker()->withId(3)->build();
        $planning       = PlanningBuilder::aPlanning(123)
            ->withBacklogTrackers($first_tracker, $second_tracker, $third_tracker)
            ->build();
        $checker->checkRemovedBacklogTrackersCanBeRemoved(
            $planning,
            PlanningParameters::fromArray(['backlog_tracker_ids' => $this->backlog_tracker_ids])
        );
    }

    public function testItReturnsIfNoTrackerRemovedAsBacklogTracker(): void
    {
        $this->backlog_tracker_ids = [1, 2, 3, 4];
        $this->expectNotToPerformAssertions();
        $this->check();
    }

    public function testItReturnsIfRemovedTrackerDoesNotHaveAAddToTopBacklogWorkflowAction(): void
    {
        $this->add_to_top_backlog_post_action_dao->expects(self::once())
            ->method('getTrackersThatHaveAtLeastOneAddToTopBacklogPostAction')
            ->willReturn([]);

        $this->check();
    }

    public function testItThrowsAnExceptionIfRemovedTrackerHasAtLeastOneAddToTopBacklogWorkflowAction(): void
    {
        $this->add_to_top_backlog_post_action_dao->expects(self::once())
            ->method('getTrackersThatHaveAtLeastOneAddToTopBacklogPostAction')
            ->with([2, 3])
            ->willReturn([
                ['name' => 'tracker01'],
            ]);

        $this->expectException(TrackerHaveAtLeastOneAddToTopBacklogPostActionException::class);
        $this->check();
    }
}
