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

namespace Tuleap\ScaledAgile\Adapter\Team;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ScaledAgile\Adapter\Program\Hierarchy\TeamTrackerNotFoundException;
use Tuleap\ScaledAgile\Adapter\Program\Hierarchy\TrackerDoesNotBelongToTeamException;
use Tuleap\ScaledAgile\Team\Creation\TeamStore;
use Tuleap\ScaledAgile\Team\TeamTracker;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class TeamTrackerAdapterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var TeamTrackerAdapter
     */
    private $adapter;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TeamStore
     */
    private $team_store;

    protected function setUp(): void
    {
        $this->team_store      = \Mockery::mock(TeamStore::class);
        $this->tracker_factory = \Mockery::mock(\TrackerFactory::class);

        $this->adapter = new TeamTrackerAdapter($this->tracker_factory, $this->team_store);
    }

    public function testItThrowsAnExceptionWhenTeamTrackerIsNotFound(): void
    {
        $team_backlog_id = 200;

        $this->tracker_factory->shouldReceive('getTrackerById')->with($team_backlog_id)->once()->andReturnNull();

        $this->expectException(TeamTrackerNotFoundException::class);
        $this->adapter->buildTeamTracker($team_backlog_id);
    }

    public function testItThrowsAnExceptionWhenTrackerDoesNotBelongToATeamProject(): void
    {
        $team_backlog_id = 200;

        $team         = new \Project(['group_id' => 101, 'group_name' => 'Team', 'unix_group_name' => 'team']);
        $team_tracker = TrackerTestBuilder::aTracker()->withProject($team)->withId($team_backlog_id)->build();
        $this->tracker_factory->shouldReceive('getTrackerById')->with($team_backlog_id)->once()->andReturn(
            $team_tracker
        );

        $this->team_store->shouldReceive('isATeam')->with($team->getGroupId())->once()->andReturnFalse();

        $this->expectException(TrackerDoesNotBelongToTeamException::class);
        $this->adapter->buildTeamTracker($team_backlog_id);
    }

    public function testItBuildsATeamTracker(): void
    {
        $team_backlog_id = 200;

        $team         = new \Project(['group_id' => 101, 'group_name' => 'Team', 'unix_group_name' => 'team']);
        $team_tracker = TrackerTestBuilder::aTracker()->withProject($team)->withId($team_backlog_id)->build();
        $this->tracker_factory->shouldReceive('getTrackerById')->with($team_backlog_id)->once()->andReturn(
            $team_tracker
        );

        $this->team_store->shouldReceive('isATeam')->with($team->getGroupId())->once()->andReturnTrue();

        $expected = new TeamTracker($team_tracker->getId(), $team->getGroupId());
        $this->assertEquals($expected, $this->adapter->buildTeamTracker($team_backlog_id));
    }
}
