<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 *  Tuleap and Enalean names and logos are registrated trademarks owned by
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

declare(strict_types=1);

namespace Tuleap\Timetracking\Report;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Timetracking\Exceptions\TimetrackingReportNotFoundException;
use Tuleap\Timetracking\Time\TimetrackingReport;
use Tuleap\Timetracking\Time\TimetrackingReportDao;
use Tuleap\Timetracking\Time\TimetrackingReportFactory;

class TimetrackingReportFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var TimetrackingReportDao
     */
    private $timetracking_report_dao;

    /**
     * @var TimetrackingReportFactory
     */
    private $timetracking_report_factory;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var Tracker
     */
    private $tracker;

    public function setUp(): void
    {
        parent::setUp();

        $this->timetracking_report_dao     = \Mockery::mock(TimetrackingReportDao::class);
        $this->tracker_factory             = \Mockery::mock(\TrackerFactory::class);
        $this->timetracking_report_factory = new TimetrackingReportFactory($this->timetracking_report_dao, $this->tracker_factory);
        $this->tracker                     = \Mockery::mock(\Tracker::class);
    }

    public function testGetReportByIdRetrievesTimeTrackingReport()
    {
        $this->timetracking_report_dao->shouldReceive("searchReportById")->andReturn(1);
        $this->timetracking_report_dao->shouldReceive("searchReportTrackersById")->andReturn(
            [
                ['tracker_id' => 1],
                ['tracker_id' => 2]
            ]
        );

        $this->tracker_factory->shouldReceive('getTrackerById')->with(1)->andReturn(null);

        $this->tracker_factory->shouldReceive('getTrackerById')->with(2)->andReturn($this->tracker);
        $this->tracker->shouldReceive('userCanView')->andReturn(true);

        $expected_result = new TimetrackingReport(1, array($this->tracker));
        $result          = $this->timetracking_report_factory->getReportById(1);

        $this->assertEquals($expected_result, $result);
    }

    public function testItThrowsAnExceptionWhenReportIsNotFound()
    {
        $this->timetracking_report_dao->shouldReceive('searchReportById')->andReturn(false);
        $this->expectException(TimetrackingReportNotFoundException::class);

        $this->timetracking_report_factory->getReportById(1);
    }
}
