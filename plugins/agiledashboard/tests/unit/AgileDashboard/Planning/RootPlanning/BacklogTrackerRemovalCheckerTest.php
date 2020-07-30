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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Planning;
use PlanningParameters;
use Tuleap\AgileDashboard\Planning\TrackerHaveAtLeastOneAddToTopBacklogPostActionException;
use Tuleap\AgileDashboard\Workflow\AddToTopBacklogPostActionDao;

final class BacklogTrackerRemovalCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var BacklogTrackerRemovalChecker
     */
    private $checker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|AddToTopBacklogPostActionDao
     */
    private $add_to_top_backlog_post_action_dao;

    protected function setUp(): void
    {
        $this->add_to_top_backlog_post_action_dao = Mockery::mock(AddToTopBacklogPostActionDao::class);

        $this->checker = new BacklogTrackerRemovalChecker($this->add_to_top_backlog_post_action_dao);
    }

    public function testItReturnsIfNoTrackerRemovedAsBacklogTracker(): void
    {
        $planning = new Planning('1', 'Root Planning', '123', '', '', [1, 2, 3]);
        $this->expectNotToPerformAssertions();

        $this->checker->checkRemovedBacklogTrackersCanBeRemoved(
            $planning,
            PlanningParameters::fromArray(['backlog_tracker_ids' => [1, 2, 3, 4]])
        );
    }

    public function testItReturnsIfRemovedTrackerDoesNotHaveAAddToTopBacklogWorkflowAction(): void
    {
        $this->add_to_top_backlog_post_action_dao->shouldReceive('getTrackersThatHaveAtLeastOneAddToTopBacklogPostAction')
            ->once()
            ->andReturn([]);

        $planning = new Planning('1', 'Root Planning', '123', '', '', [1, 2, 3]);

        $this->checker->checkRemovedBacklogTrackersCanBeRemoved(
            $planning,
            PlanningParameters::fromArray(['backlog_tracker_ids' => [1]])
        );
    }

    public function testItThrowsAnExceptionIfRemovedTrackerHasAtLeastOneAddToTopBacklogWorkflowAction(): void
    {
        $this->add_to_top_backlog_post_action_dao->shouldReceive('getTrackersThatHaveAtLeastOneAddToTopBacklogPostAction')
            ->with([2, 3])
            ->once()
            ->andReturn([
                ['name' => 'tracker01']
            ]);

        $planning = new Planning('1', 'Root Planning', '123', '', '', [1, 2, 3]);
        $this->expectException(TrackerHaveAtLeastOneAddToTopBacklogPostActionException::class);

        $this->checker->checkRemovedBacklogTrackersCanBeRemoved(
            $planning,
            PlanningParameters::fromArray(['backlog_tracker_ids' => [1]])
        );
    }
}
