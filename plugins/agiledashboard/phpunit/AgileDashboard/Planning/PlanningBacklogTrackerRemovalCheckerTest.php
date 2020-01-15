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

namespace Tuleap\AgileDashboard\Planning;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Planning;
use PlanningFactory;
use PlanningParameters;
use Tuleap\AgileDashboard\Workflow\AddToTopBacklogPostActionDao;

final class PlanningBacklogTrackerRemovalCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var PlanningBacklogTrackerRemovalChecker
     */
    private $checker;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanningFactory
     */
    private $planning_factory;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Planning
     */
    private $planning;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|AddToTopBacklogPostActionDao
     */
    private $add_to_top_backlog_post_action_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->planning_factory                   = Mockery::mock(PlanningFactory::class);
        $this->add_to_top_backlog_post_action_dao = Mockery::mock(AddToTopBacklogPostActionDao::class);

        $this->checker = new PlanningBacklogTrackerRemovalChecker(
            $this->planning_factory,
            $this->add_to_top_backlog_post_action_dao
        );

        $this->user      = Mockery::mock(PFUser::class);
        $this->planning  = Mockery::mock(Planning::class);

        $this->planning->shouldReceive('getGroupId')->andReturn('123');
        $this->planning->shouldReceive('getId')->andReturn('1');
        $this->planning->shouldReceive('getBacklogTrackersIds')->andReturn([
            1, 2, 3
        ]);
    }

    public function testItReturnsIfNoRootPlanning(): void
    {
        $this->planning_factory->shouldReceive('getRootPlanning')
            ->once()
            ->andReturnFalse();

        $this->doesNotPerformAssertions();

        $this->checker->checkRemovedBacklogTrackersCanBeRemoved(
            $this->user,
            $this->planning,
            PlanningParameters::fromArray([])
        );
    }

    public function testItReturnsIfNotARootPlanning(): void
    {
        $this->planning_factory->shouldReceive('getRootPlanning')
            ->once()
            ->andReturn(
                Mockery::mock(Planning::class)->shouldReceive('getId')->andReturn('2')->getMock()
            );

        $this->doesNotPerformAssertions();

        $this->checker->checkRemovedBacklogTrackersCanBeRemoved(
            $this->user,
            $this->planning,
            PlanningParameters::fromArray([])
        );
    }

    public function testItReturnsIfNoTrackerRemovedAsBacklogTracker(): void
    {
        $this->planning_factory->shouldReceive('getRootPlanning')
            ->once()
            ->andReturn($this->planning);

        $this->doesNotPerformAssertions();

        $this->checker->checkRemovedBacklogTrackersCanBeRemoved(
            $this->user,
            $this->planning,
            PlanningParameters::fromArray([
                'backlog_tracker_ids' => [1, 2, 3, 4]
            ])
        );
    }

    public function testItReturnsIfRemovedTrackerDoesNotHaveAAddToTopBacklogWorkflowAction(): void
    {
        $this->planning_factory->shouldReceive('getRootPlanning')
            ->once()
            ->andReturn($this->planning);

        $this->add_to_top_backlog_post_action_dao->shouldReceive('getTrackersThatHaveAtLeastOneAddToTopBacklogPostAction')
            ->once()
            ->andReturn([]);

        $this->doesNotPerformAssertions();

        $this->checker->checkRemovedBacklogTrackersCanBeRemoved(
            $this->user,
            $this->planning,
            PlanningParameters::fromArray([
                'backlog_tracker_ids' => [1]
            ])
        );
    }

    public function testItThrowsAnExceptionIfRemovedTrackerHasAtLeastOneAddToTopBacklogWorkflowAction(): void
    {
        $this->planning_factory->shouldReceive('getRootPlanning')
            ->once()
            ->andReturn($this->planning);

        $this->add_to_top_backlog_post_action_dao->shouldReceive('getTrackersThatHaveAtLeastOneAddToTopBacklogPostAction')
            ->with([2, 3])
            ->once()
            ->andReturn([
                ['name' => 'tracker01']
            ]);

        $this->expectException(TrackerHaveAtLeastOneAddToTopBacklogPostActionException::class);

        $this->checker->checkRemovedBacklogTrackersCanBeRemoved(
            $this->user,
            $this->planning,
            PlanningParameters::fromArray([
                'backlog_tracker_ids' => [1]
            ])
        );
    }
}
