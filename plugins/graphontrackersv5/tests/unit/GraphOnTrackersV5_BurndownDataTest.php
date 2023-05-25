<?php
/**
 *  Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 *
 *   This file is a part of Tuleap.
 *
 *   Tuleap is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 *   Tuleap is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\GraphOnTrackersV5;

use GraphOnTrackersV5_Burndown_Data;
use TimePeriodWithoutWeekEnd;
use Tracker_Chart_Burndown;

require_once __DIR__ . '/bootstrap.php';

//phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
final class GraphOnTrackersV5_BurndownDataTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private GraphOnTrackersV5_Burndown_Data&\PHPUnit\Framework\MockObject\MockObject $burndown_data;

    public function setUp(): void
    {
        parent::setUp();
        $this->burndown_data = $this->createMock(\GraphOnTrackersV5_Burndown_Data::class);
    }

    public function testItNormalizeDataDayByDayStartingAtStartDate(): void
    {
        $remaining_efforts = [
            20170901 => [
                5215 => null,
                5217 => null,
            ],
            20170902 => [
                5215 => '1.0000',
                5217 => null,
                5239 => null,
                5241 => '13.0000',
            ],
            20170904 => [
                5215 => '2.0000',
                5217 => '1.0000',
                5239 => '0.5000',
                5241 => '14.0000',
            ],
        ];
        $start_date        = mktime(8, 0, 0, 9, 1, 2017);
        $duration          = 5;
        $time_period       = TimePeriodWithoutWeekEnd::buildFromDuration($start_date, $duration);

        $this->burndown_data->method('getTimePeriod')->willReturn($time_period);
        $this->burndown_data->method('getRemainingEffort')->willReturn($remaining_efforts);

        $burndown = new Tracker_Chart_Burndown($this->burndown_data);

        $expected_values = [
            'Fri 01' => [null],
            'Sat 02' => [14],
            'Sun 03' => [14],
            'Mon 04' => [17.5],
            'Tue 05' => [17.5],
            'Wed 06' => [17.5],
        ];
        $burndown_values = $burndown->getComputedData();

        self::assertEquals($expected_values, $burndown_values);
    }

    public function testTtDisplaysNothingWhenRemainingEffortAreNotSet(): void
    {
        $remaining_efforts = [];
        $start_date        = mktime(8, 0, 0, 9, 1, 2017);
        $duration          = 5;
        $time_period       = TimePeriodWithoutWeekEnd::buildFromDuration($start_date, $duration);

        $this->burndown_data->method('getTimePeriod')->willReturn($time_period);
        $this->burndown_data->method('getRemainingEffort')->willReturn($remaining_efforts);

        $burndown = new Tracker_Chart_Burndown($this->burndown_data);

        $expected_values = [
            'Fri 01' => null,
            'Sat 02' => null,
            'Sun 03' => null,
            'Mon 04' => null,
            'Tue 05' => null,
            'Wed 06' => null,
        ];
        $burndown_values = $burndown->getComputedData();

        self::assertEquals($expected_values, $burndown_values);
    }

    public function testItDontDisplayFuture(): void
    {
        $remaining_efforts = [];
        $start_date        = time();
        $duration          = 5;
        $time_period       = TimePeriodWithoutWeekEnd::buildFromDuration($start_date, $duration);

        $this->burndown_data->method('getTimePeriod')->willReturn($time_period);
        $this->burndown_data->method('getRemainingEffort')->willReturn($remaining_efforts);

        $burndown = new Tracker_Chart_Burndown($this->burndown_data);

        $burndown_values = $burndown->getComputedData();
        $expected_values = [
            (new \DateTimeImmutable())->format('D d') => null,
            (new \DateTimeImmutable("+1 day"))->format('D d') => null,
            (new \DateTimeImmutable("+2 day"))->format('D d') => null,
            (new \DateTimeImmutable("+3 day"))->format('D d') => null,
            (new \DateTimeImmutable("+4 day"))->format('D d') => null,
            (new \DateTimeImmutable("+5 day"))->format('D d') => null,
        ];

        self::assertEquals($expected_values, $burndown_values);
    }

    public function testItDisplaysNothingWhenEndBurndownDateIsBeforeStartDateAskedByUser(): void
    {
        $remaining_efforts = [
            20170901 => [
                5215 => null,
                5217 => null,
            ],
            20170902 => [
                5215 => '1.0000',
                5217 => null,
                5239 => null,
                5241 => '13.0000',
            ],
            20170904 => [
                5215 => '2.0000',
                5217 => '1.0000',
                5239 => '0.5000',
                5241 => '14.0000',
            ],
        ];
        $start_date        = mktime(8, 0, 0, 9, 1, 2016);
        $duration          = 5;
        $time_period       = TimePeriodWithoutWeekEnd::buildFromDuration($start_date, $duration);

        $this->burndown_data->method('getTimePeriod')->willReturn($time_period);
        $this->burndown_data->method('getRemainingEffort')->willReturn($remaining_efforts);

        $burndown = new Tracker_Chart_Burndown($this->burndown_data);

        $expected_values = [
            'Thu 01' => null,
            'Fri 02' => null,
            'Sat 03' => null,
            'Sun 04' => null,
            'Mon 05' => null,
            'Tue 06' => null,
        ];
        $burndown_values = $burndown->getComputedData();

        self::assertEquals($expected_values, $burndown_values);
    }

    public function testItShouldTakeIntoAccountWhenValueFallToZero(): void
    {
        $remaining_efforts = [
            20170901 => [
                5215 => null,
                5217 => null,
            ],
            20170902 => [
                5215 => '1.0000',
                5217 => null,
                5239 => null,
                5241 => '13.0000',
            ],
            20170904 => [
                5215 => '0',
                5217 => '0',
                5239 => '0',
                5241 => '0',
            ],
        ];
        $start_date        = mktime(8, 0, 0, 9, 1, 2017);
        $duration          = 5;
        $time_period       = TimePeriodWithoutWeekEnd::buildFromDuration($start_date, $duration);

        $this->burndown_data->method('getTimePeriod')->willReturn($time_period);
        $this->burndown_data->method('getRemainingEffort')->willReturn($remaining_efforts);

        $burndown = new Tracker_Chart_Burndown($this->burndown_data);

        $expected_values = [
            'Fri 01' => [null],
            'Sat 02' => [14],
            'Sun 03' => [14],
            'Mon 04' => [0],
            'Tue 05' => [0],
            'Wed 06' => [0],
        ];
        $burndown_values = $burndown->getComputedData();

        self::assertEquals($expected_values, $burndown_values);
    }

    public function testItShouldComputeIdealBurndownForDisplay(): void
    {
        $remaining_efforts = [
            20170901 => [
                5215 => null,
                5217 => null,
            ],
            20170902 => [
                5215 => 500,
                5241 => 40,
            ],
            20170903 => [
                5215 => 0,
                5217 => 0,
                5239 => 0,
                5241 => 0,
            ],
        ];
        $start_date        = mktime(8, 0, 0, 9, 1, 2017);
        $duration          = 20;
        $time_period       = TimePeriodWithoutWeekEnd::buildFromDuration($start_date, $duration);

        $this->burndown_data->method('getTimePeriod')->willReturn($time_period);
        $this->burndown_data->method('getRemainingEffort')->willReturn($remaining_efforts);

        $burndown = new Tracker_Chart_Burndown($this->burndown_data);
        $burndown->prepareDataForGraph($remaining_efforts);

        $ideal_burndown_by_day = $burndown->getGraphDataIdealBurndown();

        self::assertEquals($ideal_burndown_by_day[0], 540.0);
        self::assertEquals($ideal_burndown_by_day[1], 486.0);
        self::assertEquals($ideal_burndown_by_day[2], 432.0);
    }

    public function testItShouldAddPreviousValuesWhenStartDateIsToday(): void
    {
        $start_date = time();
        $yesterday  = (new \DateTimeImmutable("-1 day"))->format('Ymd');

        $remaining_efforts = [
            $yesterday => [
                5215 => 10,
                5217 => 20,
            ],
        ];
        $duration          = 5;
        $time_period       = TimePeriodWithoutWeekEnd::buildFromDuration($start_date, $duration);

        $this->burndown_data->method('getTimePeriod')->willReturn($time_period);
        $this->burndown_data->method('getRemainingEffort')->willReturn($remaining_efforts);

        $burndown = new Tracker_Chart_Burndown($this->burndown_data);

        $burndown_values = $burndown->getComputedData();
        $expected_values = [
            (new \DateTimeImmutable())->format('D d') => [30],
            (new \DateTimeImmutable("+1 day"))->format('D d') => null,
            (new \DateTimeImmutable("+2 day"))->format('D d') => null,
            (new \DateTimeImmutable("+3 day"))->format('D d') => null,
            (new \DateTimeImmutable("+4 day"))->format('D d') => null,
            (new \DateTimeImmutable("+5 day"))->format('D d') => null,
        ];

        self::assertEquals($expected_values, $burndown_values);
    }
}
