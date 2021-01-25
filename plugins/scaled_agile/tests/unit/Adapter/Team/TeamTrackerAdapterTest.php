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
use Tuleap\ScaledAgile\Adapter\Program\Hierarchy\TeamCanOnlyHaveOneBacklogTrackerException;
use Tuleap\ScaledAgile\Adapter\Program\Hierarchy\TeamTrackerMustBeInPlannableTopBacklogException;
use Tuleap\ScaledAgile\Adapter\Program\Hierarchy\TeamTrackerNotFoundException;
use Tuleap\ScaledAgile\Adapter\Program\Hierarchy\TrackerDoesNotBelongToTeamException;
use Tuleap\ScaledAgile\Program\BuildPlanning;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\Planning;
use Tuleap\ScaledAgile\Project;
use Tuleap\ScaledAgile\ScaledAgileTracker;
use Tuleap\ScaledAgile\Team\Creation\TeamStore;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class TeamTrackerAdapterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|BuildPlanning
     */
    private $planning_adapter;

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
        $this->team_store       = \Mockery::mock(TeamStore::class);
        $this->tracker_factory  = \Mockery::mock(\TrackerFactory::class);
        $this->planning_adapter = \Mockery::mock(BuildPlanning::class);

        $this->adapter = new TeamTrackerAdapter($this->tracker_factory, $this->team_store, $this->planning_adapter);
    }

    public function testItThrowsAnExceptionWhenTeamTrackerIsNotFound(): void
    {
        $team_backlog_ids = [200];
        $user             = UserTestBuilder::aUser()->build();

        $this->tracker_factory->shouldReceive('getTrackerById')->with(200)->once()->andReturnNull();

        $this->expectException(TeamTrackerNotFoundException::class);
        $this->adapter->buildTeamTrackers($team_backlog_ids, $user);
    }

    public function testItThrowsAnExceptionWhenTrackerDoesNotBelongToATeamProject(): void
    {
        $team_backlog_ids = [200];
        $user             = UserTestBuilder::aUser()->build();

        $team         = new \Project(['group_id' => 101, 'group_name' => 'Team', 'unix_group_name' => 'team']);
        $team_tracker = TrackerTestBuilder::aTracker()->withProject($team)->withId(200)->build();
        $this->tracker_factory->shouldReceive('getTrackerById')->with(200)->once()->andReturn(
            $team_tracker
        );

        $this->team_store->shouldReceive('isATeam')->with($team->getGroupId())->once()->andReturnFalse();

        $this->expectException(TrackerDoesNotBelongToTeamException::class);
        $this->adapter->buildTeamTrackers($team_backlog_ids, $user);
    }

    public function testItThrowsAnExceptionWhenMoreThanOneTeamTrackerIsProvided(): void
    {
        $team_backlog_ids = [200, 300];
        $user             = UserTestBuilder::aUser()->build();

        $team         = new \Project(['group_id' => 101, 'group_name' => 'Team', 'unix_group_name' => 'team']);
        $team_tracker = TrackerTestBuilder::aTracker()->withProject($team)->withId(200)->build();
        $this->tracker_factory->shouldReceive('getTrackerById')->with(200)->once()->andReturn(
            $team_tracker
        );
        $other_team_tracker = TrackerTestBuilder::aTracker()->withProject($team)->withId(300)->build();
        $this->tracker_factory->shouldReceive('getTrackerById')->with(300)->andReturn(
            $other_team_tracker
        );

        $planning_tracker = TrackerTestBuilder::aTracker()->withId(104)->build();
        $planning         = new Planning(
            new ScaledAgileTracker($planning_tracker),
            1,
            'Release Planning',
            [200, 300],
            new Project(1, "my_project", "My project")
        );
        $this->planning_adapter->shouldReceive('buildRootPlanning')->andReturn($planning);

        $this->team_store->shouldReceive('isATeam')->with($team->getGroupId())->andReturnTrue();
        $this->expectException(TeamCanOnlyHaveOneBacklogTrackerException::class);

        $this->adapter->buildTeamTrackers($team_backlog_ids, $user);
    }

    public function testItThrowsAnExceptionWhenTrackerIsNotATopBacklogOne(): void
    {
        $team_backlog_ids = [200];
        $user             = UserTestBuilder::aUser()->build();

        $team         = new \Project(['group_id' => 101, 'group_name' => 'Team', 'unix_group_name' => 'team']);
        $team_tracker = TrackerTestBuilder::aTracker()->withProject($team)->withId(200)->build();
        $this->tracker_factory->shouldReceive('getTrackerById')->with(200)->once()->andReturn(
            $team_tracker
        );

        $planning_tracker = TrackerTestBuilder::aTracker()->withId(104)->build();
        $planning         = new Planning(
            new ScaledAgileTracker($planning_tracker),
            1,
            'Release Planning',
            [500, 600],
            new Project(1, "my_project", "My project")
        );
        $this->planning_adapter->shouldReceive('buildRootPlanning')->andReturn($planning);

        $this->team_store->shouldReceive('isATeam')->with($team->getGroupId())->once()->andReturnTrue();

        $this->expectException(TeamTrackerMustBeInPlannableTopBacklogException::class);
        $this->adapter->buildTeamTrackers($team_backlog_ids, $user);
    }

    public function testItBuildsATeamTracker(): void
    {
        $team_backlog_ids = [200];
        $user             = UserTestBuilder::aUser()->build();

        $team         = new \Project(['group_id' => 101, 'group_name' => 'Team', 'unix_group_name' => 'team']);
        $team_tracker = TrackerTestBuilder::aTracker()->withProject($team)->withId(200)->build();
        $this->tracker_factory->shouldReceive('getTrackerById')->with(200)->once()->andReturn(
            $team_tracker
        );

        $planning_tracker = TrackerTestBuilder::aTracker()->withId(104)->build();
        $planning         = new Planning(
            new ScaledAgileTracker($planning_tracker),
            1,
            'Release Planning',
            [200],
            new Project(1, "my_project", "My project")
        );
        $this->planning_adapter->shouldReceive('buildRootPlanning')->andReturn($planning);

        $this->team_store->shouldReceive('isATeam')->with($team->getGroupId())->once()->andReturnTrue();

        $this->adapter->buildTeamTrackers($team_backlog_ids, $user);
    }
}
