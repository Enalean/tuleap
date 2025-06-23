<?php
/**
 * Copyright Enalean (c) 2018 - Present. All rights reserved.
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

use Tuleap\Timetracking\Exceptions\TimetrackingReportNotFoundException;
use Tuleap\Timetracking\Time\TimetrackingReport;
use Tuleap\Timetracking\Time\TimetrackingReportDao;
use Tuleap\Timetracking\Time\TimetrackingReportFactory;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TimetrackingReportFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TimetrackingReportDao
     */
    private $timetracking_report_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\TrackerFactory
     */
    private $tracker_factory;
    private TimetrackingReportFactory $timetracking_report_factory;

    public function setUp(): void
    {
        parent::setUp();

        $this->timetracking_report_dao     = $this->createMock(TimetrackingReportDao::class);
        $this->tracker_factory             = $this->createMock(\TrackerFactory::class);
        $this->timetracking_report_factory = new TimetrackingReportFactory(
            $this->timetracking_report_dao,
            $this->tracker_factory
        );
    }

    public function testGetReportByIdRetrievesTimeTrackingReport(): void
    {
        $tracker = $this->createMock(\Tuleap\Tracker\Tracker::class);
        $tracker->method('userCanView')->willReturn(true);

        $this->timetracking_report_dao->method('searchReportById')->willReturn(1);
        $this->timetracking_report_dao->method('searchReportTrackersById')->willReturn(
            [
                ['tracker_id' => 1],
                ['tracker_id' => 2],
            ]
        );

        $this->tracker_factory->method('getTrackerById')->willReturnMap([
            [1, null],
            [2, $tracker],
        ]);

        $expected_result = new TimetrackingReport(1, [$tracker]);
        $result          = $this->timetracking_report_factory->getReportById(1);

        self::assertEquals($expected_result, $result);
    }

    public function testItThrowsAnExceptionWhenReportIsNotFound(): void
    {
        $this->timetracking_report_dao->method('searchReportById')->willReturn(false);
        $this->expectException(TimetrackingReportNotFoundException::class);

        $this->timetracking_report_factory->getReportById(1);
    }
}
