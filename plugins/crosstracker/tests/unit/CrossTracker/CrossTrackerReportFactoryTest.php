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

declare(strict_types=1);

namespace Tuleap\CrossTracker;

use Tuleap\CrossTracker\Tests\Stub\Report\RetrieveReportStub;
use Tuleap\CrossTracker\Tests\Stub\Report\SearchTrackersOfReportStub;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveTrackerStub;

final class CrossTrackerReportFactoryTest extends TestCase
{
    private const REPORT_ID = 1;
    private \Tracker $tracker;
    private RetrieveTrackerStub $tracker_factory;
    private SearchTrackersOfReportStub $trackers_searcher;

    public function setUp(): void
    {
        $this->trackers_searcher = SearchTrackersOfReportStub::withTrackers([
            'report_id' => self::REPORT_ID,
            'trackers'  => [3, 4],
        ]);

        $this->tracker         = TrackerTestBuilder::aTracker()->withId(4)->build();
        $this->tracker_factory = RetrieveTrackerStub::withTrackers($this->tracker);
    }

    /**
     * @throws CrossTrackerReportNotFoundException
     */
    private function getById(int $report_id, bool $is_expert_mode): CrossTrackerReport
    {
        $report_retriever = RetrieveReportStub::withReports(
            ['id' => self::REPORT_ID, 'expert_query' => '', 'expert_mode' => (int) $is_expert_mode]
        );
        $factory          = new CrossTrackerReportFactory(
            $report_retriever,
            $this->trackers_searcher,
            $this->tracker_factory
        );
        return $factory->getById($report_id);
    }

    public function testItThrowsAnExceptionWhenReportIsNotFound(): void
    {
        $this->expectException(CrossTrackerReportNotFoundException::class);
        $this->getById(404, false);
    }

    public function testItReturnsADefaultCrossTrackerReport(): void
    {
        $expected_result = new CrossTrackerDefaultReport(self::REPORT_ID, '', [$this->tracker]);

        $result = $this->getById(self::REPORT_ID, false);

        self::assertInstanceOf(CrossTrackerDefaultReport::class, $result);
        self::assertEquals($expected_result, $result);
    }

    public function testItReturnsAnExpertCrossTrackerReport(): void
    {
        $expected_result = new CrossTrackerExpertReport(self::REPORT_ID, '');

        $result = $this->getById(self::REPORT_ID, true);

        self::assertInstanceOf(CrossTrackerExpertReport::class, $result);
        self::assertEquals($expected_result, $result);
    }
}
