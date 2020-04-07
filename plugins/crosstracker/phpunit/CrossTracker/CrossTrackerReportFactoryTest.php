<?php
/**
 *  Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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
 */

namespace Tuleap\CrossTracker;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';

class CrossTrackerReportFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Tracker
     */
    private $tracker_2;
    /**
     * @var \Tracker
     */
    private $tracker_1;
    /**
     * @var \PFUser
     */
    private $user;
    /**
     * @var CrossTrackerReportFactory
     */
    private $cross_tracker_factory;
    /**
     * @var \TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var CrossTrackerReportDao
     */
    private $report_dao;

    public function setUp(): void
    {
        parent::setUp();

        $globals = array_merge([], $GLOBALS);

        $this->report_dao            = \Mockery::spy(\Tuleap\CrossTracker\CrossTrackerReportDao::class);
        $this->tracker_factory       = \Mockery::spy(\TrackerFactory::class);
        $this->cross_tracker_factory = new CrossTrackerReportFactory($this->report_dao, $this->tracker_factory);

        $this->user = \Mockery::spy(\PFUser::class);
        $this->user->shouldReceive('getId')->andReturn(101);

        $this->tracker_1 = \Mockery::spy(\Tracker::class);
        $this->tracker_1->shouldReceive('getId')->andReturn(1);

        $this->tracker_2 = \Mockery::spy(\Tracker::class);
        $this->tracker_2->shouldReceive('getId')->andReturn(2);

        $GLOBALS = $globals;
    }

    public function testItThrowsAnExceptionWhenReportIsNotFound()
    {
        $this->report_dao->shouldReceive('searchReportById')->andReturn(false);
        $this->expectException(\Tuleap\CrossTracker\CrossTrackerReportNotFoundException::class);

        $this->cross_tracker_factory->getById(1);
    }

    public function testItDoesNotThrowsAnExceptionWhenTrackerIsNotFound()
    {
        $this->report_dao->shouldReceive('searchReportById')->andReturn(
            array("id" => 1, "expert_query" => "")
        );

        $this->report_dao->shouldReceive('searchReportTrackersById')->andReturn(
            array(
                array("tracker_id" => 1),
                array("tracker_id" => 2)
            )
        );

        $this->tracker_factory->shouldReceive('getTrackerById')->with(1)->andReturn(null);
        $this->tracker_factory->shouldReceive('getTrackerById')->with(2)->andReturn($this->tracker_2);

        $this->tracker_2->shouldReceive('userCanView')->andReturn(true);

        $expected_result = new CrossTrackerReport(1, '', array($this->tracker_2));

        $this->assertEquals(
            $this->cross_tracker_factory->getById(1),
            $expected_result
        );
    }
}
