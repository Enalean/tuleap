<?php
/**
 * Copyright Enalean (c) 2018-Present. All rights reserved.
 *
 *  Tuleap and Enalean names and logos are registered trademarks owned by
 *  Enalean SAS. All other trademarks or names are properties of their respective
 *  owners.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Tuleap\Timetracking\Time;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Tracker;
use Tracker_REST_TrackerRestBuilder;
use TrackerFactory;
use Tuleap\Timetracking\Admin\AdminDao;
use Tuleap\Timetracking\Permissions\PermissionsRetriever;
use Tuleap\Timetracking\REST\v1\TimetrackingOverviewRepresentationsBuilder;
use Tuleap\Tracker\REST\CompleteTrackerRepresentation;
use Tuleap\Tracker\TrackerColor;

class TimetrackingOverviewRepresentationsBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var AdminDao
     */
    private $admin_dao;

    /**
     * @var PermissionsRetriever
     */
    private $permissions_retriever;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var Tracker_REST_TrackerRestBuilder
     */
    private $tracker_rest_builder;

    /**
     * @var TimetrackingOverviewRepresentationsBuilder
     */
    private $timetracking_overview_representations_builder;

    /**
     * @var Tracker
     */
    private $tracker;

    /**
     * @var \PFUser
     */
    private $user;

    /**
     * @var Project
     */
    private $project;

    public function setUp(): void
    {
        parent::setUp();
        $globals = array_merge([], $GLOBALS);

        $this->admin_dao = \Mockery::spy(
            \Tuleap\Timetracking\Admin\AdminDao::class
        );

        $this->permissions_retriever = \Mockery::spy(
            \Tuleap\Timetracking\Permissions\PermissionsRetriever::class
        );

        $this->tracker_factory                               = \Mockery::spy(\TrackerFactory::class);
        $this->tracker_rest_builder                          = \Mockery::spy(\Tracker_REST_TrackerRestBuilder::class);
        $this->timetracking_overview_representations_builder = new TimetrackingOverviewRepresentationsBuilder(
            $this->admin_dao,
            $this->permissions_retriever,
            $this->tracker_factory,
            $this->tracker_rest_builder
        );

        $this->user    = \Mockery::spy(\PFUser::class);
        $this->project = \Mockery::spy(Project::class);
        $this->user->allows()->getId()->andReturns(102);

        $this->tracker = \Mockery::spy(Tracker::class);
        $this->tracker->shouldReceive(
            [
                'getId'    => 16,
                'getColor' => TrackerColor::default()
            ]
        );

        $GLOBALS = $globals;
    }

    public function testGetTrackersMinimalRepresentationWithTimetracking()
    {
        $this->permissions_retriever->allows()->userCanSeeAggregatedTimesInTracker(
            $this->user,
            $this->tracker
        )->andReturns(true);
        $this->admin_dao->shouldReceive("foundRows")->andReturns(1);
        $this->admin_dao->shouldReceive('getProjectTrackersWithEnabledTimetracking')->andReturns(
            [
                ['tracker_id' => 16]
            ]
        );

        $this->tracker_factory->shouldReceive('getTrackerById')->with(16)->andReturn($this->tracker);

        $this->tracker->shouldReceive('userCanView')->with($this->user)->andReturn(true);
        $result = $this->timetracking_overview_representations_builder->getTrackersMinimalRepresentationsWithTimetracking(
            $this->user,
            $this->project,
            10,
            0
        );

        $this->assertEquals($this->tracker->getId(), $result["trackers"][0]->id);
        $this->assertEquals(1, $result["total_trackers"]);
    }

    public function testGetTrackersFullRepresentationWithTimetracking()
    {
        $tracker_representation     = \Mockery::mock(CompleteTrackerRepresentation::class);
        $tracker_representation->id = $this->tracker->getId();

        $this->permissions_retriever->allows()->userCanSeeAggregatedTimesInTracker(
            $this->user,
            $this->tracker
        )->andReturns(true);

        $this->admin_dao->shouldReceive("foundRows")->andReturns(1);
        $this->admin_dao->shouldReceive('getProjectTrackersWithEnabledTimetracking')->andReturns(
            [
                ['tracker_id' => 16]
            ]
        );

        $this->tracker_factory->shouldReceive('getTrackerById')->with(16)->andReturn($this->tracker);
        $this->tracker_rest_builder->shouldReceive('getTrackerRepresentationInTrackerContext')->with(
            $this->user,
            $this->tracker
        )->andReturn($tracker_representation);

        $this->tracker->shouldReceive('userCanView')->with($this->user)->andReturn(true);
        $result = $this->timetracking_overview_representations_builder->getTrackersFullRepresentationsWithTimetracking(
            $this->user,
            $this->project,
            10,
            0
        );

        $this->assertEquals($tracker_representation->id, $result["trackers"][0]->id);
        $this->assertEquals(1, $result["total_trackers"]);
    }
}
