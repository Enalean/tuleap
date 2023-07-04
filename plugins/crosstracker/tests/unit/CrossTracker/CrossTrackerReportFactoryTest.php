<?php
/**
 *  Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class CrossTrackerReportFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \Tracker $tracker;
    private CrossTrackerReportFactory $cross_tracker_factory;
    private \TrackerFactory&MockObject $tracker_factory;
    private CrossTrackerReportDao&MockObject $report_dao;

    public function setUp(): void
    {
        parent::setUp();

        $this->report_dao            = $this->createMock(\Tuleap\CrossTracker\CrossTrackerReportDao::class);
        $this->tracker_factory       = $this->createMock(\TrackerFactory::class);
        $this->cross_tracker_factory = new CrossTrackerReportFactory($this->report_dao, $this->tracker_factory);

        $this->tracker = TrackerTestBuilder::aTracker()->withId(2)->build();
    }

    public function testItThrowsAnExceptionWhenReportIsNotFound(): void
    {
        $this->report_dao->method('searchReportById')->willReturn(false);
        $this->expectException(\Tuleap\CrossTracker\CrossTrackerReportNotFoundException::class);

        $this->cross_tracker_factory->getById(1);
    }

    public function testItDoesNotThrowsAnExceptionWhenTrackerIsNotFound(): void
    {
        $this->report_dao->method('searchReportById')->willReturn(
            ["id" => 1, "expert_query" => ""]
        );

        $this->report_dao->method('searchReportTrackersById')->willReturn(
            [
                ["tracker_id" => 1],
                ["tracker_id" => 2],
            ]
        );

        $this->tracker_factory->method('getTrackerById')->willReturnMap([
            [1, null],
            [2, $this->tracker],
        ]);

        $expected_result = new CrossTrackerReport(1, '', [$this->tracker]);

        self::assertEquals(
            $this->cross_tracker_factory->getById(1),
            $expected_result
        );
    }
}
