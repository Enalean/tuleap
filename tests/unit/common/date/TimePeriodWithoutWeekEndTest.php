<?php
/**
 * Copyright Enalean (c) 2012 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Date;

use DateTime;
use TimePeriodWithoutWeekEnd;

class TimePeriodWithoutWeekEndTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private int $week_day_timestamp;
    private int $following_week_day_timestamp;
    private string $request_time;

    protected function setUp(): void
    {
        $week_day                           = new DateTime('2016-02-01');
        $this->week_day_timestamp           = $week_day->getTimestamp();
        $following_week_day                 = new DateTime('2016-02-02');
        $this->following_week_day_timestamp = $following_week_day->getTimestamp();

        $this->request_time = $_SERVER['REQUEST_TIME'];
    }

    protected function tearDown(): void
    {
        $_SERVER['REQUEST_TIME'] = $this->request_time;
    }

    public function testItProvidesAListOfDaysWhileExcludingWeekends()
    {
        $start_date  = mktime(0, 0, 0, 7, 4, 2012);
        $time_period = TimePeriodWithoutWeekEnd::buildFromDuration($start_date, 4);

        self::assertSame(
            ['Wed 04', 'Thu 05', 'Fri 06', 'Mon 09', 'Tue 10'],
            $time_period->getHumanReadableDates()
        );
    }

    public function testItProvidesTheEndDate()
    {
        $start_date  = mktime(0, 0, 0, 7, 4, 2012);
        $time_period = TimePeriodWithoutWeekEnd::buildFromDuration($start_date, 4);

        self::assertSame('Tue 10', date('D d', $time_period->getEndDate()));
    }

    public function testItProvidesTheCorrectNumberOfDayWhenLastDateOfBurndownIsBeforeToday()
    {
        $start_date  = mktime(23, 59, 59, 11, 5, 2016);
        $time_period = TimePeriodWithoutWeekEnd::buildFromDuration($start_date, 4);

        $today      = mktime(23, 59, 59, 11, 12, 2016);
        $timestamps = $time_period->getCountDayUntilDate($today);

        self::assertSame(5, $timestamps);
    }

    public function testItProvidesTheCorrectNumberOfDayWhenLastDateOfBurndownIsAfterToday()
    {
        $start_date  = mktime(23, 59, 59, 11, 5, 2016);
        $time_period = TimePeriodWithoutWeekEnd::buildFromDuration($start_date, 4);

        $today      = mktime(23, 59, 59, 11, 8, 2016);
        $timestamps = $time_period->getCountDayUntilDate($today);

        self::assertSame(1, $timestamps);
    }

    public function testDoesNotAssumeTheEndDateWhenDurationIsNotProvided(): void
    {
        $start_date  = mktime(23, 59, 59, 1, 20, 2020);
        $time_period = TimePeriodWithoutWeekEnd::buildFromDuration($start_date, null);

        self::assertNull($time_period->getDuration());
        self::assertNull($time_period->getEndDate());
    }

    public function testDoesNotAssumeTheEndDateWhenStartDateIsNotProvided(): void
    {
        $start_date  = null;
        $time_period = TimePeriodWithoutWeekEnd::buildFromDuration($start_date, 12);

        self::assertEquals(12, $time_period->getDuration());
        self::assertNull($time_period->getEndDate());
    }

    /**
     * @dataProvider providerForNumberOfDaysSinceStart
     */
    public function testGetNumberOfDaysSinceStart(
        $start_date,
        $duration,
        $current_time,
        $expected_number_of_days_since_start,
    ): void {
        $time_period = TimePeriodWithoutWeekEnd::buildFromDuration($start_date, $duration);

        $_SERVER['REQUEST_TIME'] = $current_time;

        self::assertSame($expected_number_of_days_since_start, $time_period->getNumberOfDaysSinceStart());
    }

    public static function providerForNumberOfDaysSinceStart(): array
    {
        return [
            'It does not count the start date' => [
                mktime(0, 0, 0, 1, 31, 2014),
                15,
                mktime(0, 0, 0, 1, 31, 2014),
                0,
            ],
            'It counts the next day as one day' => [
                mktime(0, 0, 0, 2, 3, 2014),
                15,
                mktime(0, 0, 0, 2, 4, 2014),
                1,
            ],
            'It counts a week as five days' => [
                mktime(0, 0, 0, 2, 3, 2014),
                15,
                mktime(0, 0, 0, 2, 10, 2014),
                5,
            ],
            'It counts a weekend as nothing' => [
                mktime(0, 0, 0, 2, 7, 2014),
                15,
                mktime(0, 0, 0, 2, 10, 2014),
                1,
            ],
            'It excludes all weekends' => [
                mktime(0, 0, 0, 1, 31, 2014),
                15,
                mktime(0, 0, 0, 2, 27, 2014),
                19,
            ],
            'It ignores future start date' => [
                mktime(0, 0, 0, 1, 31, 2014),
                15,
                mktime(0, 0, 0, 2, 27, 2013),
                0,
            ],
        ];
    }

    /**
     * @dataProvider providerForIsTodayWithinTimePeriod
     */
    public function testIsTodayWitthinTimePeriod(
        $start_date,
        $duration,
        $current_time,
        $should_today_be_within_time_period,
    ): void {
        $time_period = TimePeriodWithoutWeekEnd::buildFromDuration($start_date, $duration);

        $_SERVER['REQUEST_TIME'] = $current_time;

        self::assertEquals($should_today_be_within_time_period, $time_period->isTodayWithinTimePeriod());
    }

    public static function providerForIsTodayWithinTimePeriod(): array
    {
        return [
            'It accepts today' => [
                mktime(0, 0, 0, 2, 6, 2014),
                10,
                mktime(0, 0, 0, 2, 6, 2014),
                true,
            ],
            'It accepts today if zero duration' => [
                mktime(0, 0, 0, 2, 6, 2014),
                0,
                mktime(0, 0, 0, 2, 6, 2014),
                true,
            ],
            'It refuses tomorrow' => [
                mktime(0, 0, 0, 2, 7, 2014),
                10,
                mktime(0, 0, 0, 2, 6, 2014),
                false,
            ],
            'It works in standard case' => [
                mktime(0, 0, 0, 2, 7, 2014),
                10,
                mktime(0, 0, 0, 2, 13, 2014),
                true,
            ],
            'It accepts last day of period' => [
                mktime(0, 0, 0, 2, 5, 2014),
                10,
                mktime(0, 0, 0, 2, 19, 2014),
                true,
            ],
            'It refuses the day after the last day of period' => [
                mktime(0, 0, 0, 2, 5, 2014),
                9,
                mktime(0, 0, 0, 2, 19, 2014),
                false,
            ],
        ];
    }

    /**
     * @dataProvider providerForGetNumberOfDaysUntilEnd
     */
    public function testGetNumberOfDaysUntilEnd(
        $start_date,
        $duration,
        $current_time,
        $expected_number_of_days_until_end,
    ): void {
        $time_period = TimePeriodWithoutWeekEnd::buildFromDuration($start_date, $duration);

        $_SERVER['REQUEST_TIME'] = $current_time;

        self::assertSame($expected_number_of_days_until_end, $time_period->getNumberOfDaysUntilEnd());
    }

    public static function providerForGetNumberOfDaysUntilEnd(): array
    {
        return [
            'It lets the full duration at start' => [
                mktime(0, 0, 0, 2, 3, 2014),
                10,
                mktime(0, 0, 0, 2, 3, 2014),
                10,
            ],
            'It lets duration minus one the day after' => [
                mktime(0, 0, 0, 2, 3, 2014),
                10,
                mktime(0, 0, 0, 2, 4, 2014),
                9,
            ],
            'It lets five days during the weekend at the middle of the two sprints' => [
                mktime(0, 0, 0, 2, 3, 2014),
                10,
                mktime(0, 0, 0, 2, 8, 2014),
                5,
            ],
            'It lets five days at the beginning of second week' => [
                mktime(0, 0, 0, 2, 3, 2014),
                10,
                mktime(0, 0, 0, 2, 10, 2014),
                5,
            ],
            'It lets one day on the last day of sprint' => [
                mktime(0, 0, 0, 2, 3, 2014),
                10,
                mktime(0, 0, 0, 2, 14, 2014),
                1,
            ],
            'It is zero during the weekend just before the end date' => [
                mktime(0, 0, 0, 2, 3, 2014),
                10,
                mktime(0, 0, 0, 2, 15, 2014),
                0,
            ],
            'It zero when the time has come' => [
                mktime(0, 0, 0, 2, 3, 2014),
                10,
                mktime(0, 0, 0, 2, 17, 2014),
                0,
            ],
            'It is -4 the friday after the end of the sprint' => [
                mktime(0, 0, 0, 2, 3, 2014),
                10,
                mktime(0, 0, 0, 2, 21, 2014),
                -4,
            ],
            'It is -5 the weekend after the end of the sprint' => [
                mktime(0, 0, 0, 2, 3, 2014),
                10,
                mktime(0, 0, 0, 2, 22, 2014),
                -5,
            ],
            'It adds the missing day when start date is in the future' => [
                mktime(0, 0, 0, 2, 4, 2014),
                10,
                mktime(0, 0, 0, 2, 3, 2014),
                11,
            ],
            'It adds missing day without weekend when start date is in the future' => [
                mktime(0, 0, 0, 2, 4, 2014),
                10,
                mktime(0, 0, 0, 1, 31, 2014),
                12,
            ],
            'It continues when the end date is over' => [
                mktime(0, 0, 0, 1, 14, 2014),
                14,
                mktime(0, 0, 0, 2, 18, 2014),
                -11,
            ],
        ];
    }

    public function testItProcessesNegativeDuration()
    {
        $time_period = TimePeriodWithoutWeekEnd::buildFromDuration($this->week_day_timestamp, -2);

        self::assertSame($this->week_day_timestamp, $time_period->getEndDate());
    }

    public function testItProcessesNullDuration()
    {
        $time_period = TimePeriodWithoutWeekEnd::buildFromDuration($this->week_day_timestamp, 0);

        self::assertSame($this->week_day_timestamp, $time_period->getEndDate());
    }

    public function testItProcessesNullDurationWithAWeekEnd()
    {
        $week_end_day  = new DateTime('2016-02-06');
        $next_week_day = new DateTime('2016-02-08');
        $time_period   = TimePeriodWithoutWeekEnd::buildFromDuration($week_end_day->getTimestamp(), 0);

        self::assertSame($next_week_day->getTimestamp(), $time_period->getEndDate());
    }

    public function testItProcessesPositiveDuration()
    {
        $time_period = TimePeriodWithoutWeekEnd::buildFromDuration($this->week_day_timestamp, 1);

        self::assertSame($this->following_week_day_timestamp, $time_period->getEndDate());
    }

    public function testItProcessesFloatDuration()
    {
        $time_period = TimePeriodWithoutWeekEnd::buildFromDuration($this->week_day_timestamp, 0.21);

        self::assertSame($this->following_week_day_timestamp, $time_period->getEndDate());
    }

    public function testItProcessesPositiveDurationAsStringValue()
    {
        $time_period = TimePeriodWithoutWeekEnd::buildFromDuration($this->week_day_timestamp, "1");

        self::assertSame($this->following_week_day_timestamp, $time_period->getEndDate());
    }

    public function testItProcessesFloatDurationAsStringValue()
    {
        $time_period = TimePeriodWithoutWeekEnd::buildFromDuration($this->week_day_timestamp, "0.21");

        self::assertSame($this->following_week_day_timestamp, $time_period->getEndDate());
    }

    /**
     * @dataProvider provideEndDateData
     */
    public function testCreationFromEndDate(
        string $start_date,
        string $end_date,
        string $expected_end_date,
        int $expected_duration,
        ?string $expected_error_message,
    ): void {
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        if ($expected_error_message === null) {
            $logger->expects(self::never())->method('warning');
        } else {
            $logger->expects(self::once())->method('warning')->with($expected_error_message);
        }

        $start_date_timestamp = (new \DateTime($start_date))->getTimestamp();
        $end_date_timestamp   = (new DateTime($end_date))->getTimestamp();
        $time_period          = TimePeriodWithoutWeekEnd::buildFromEndDate(
            $start_date_timestamp,
            $end_date_timestamp,
            $logger
        );

        self::assertSame($expected_duration, $time_period->getDuration());

        $expected_end_date_timestamp = (new DateTime($expected_end_date))->getTimestamp();
        self::assertSame(
            $expected_end_date_timestamp,
            $time_period->getEndDate(),
            "End date should be $expected_end_date"
        );
    }

    public static function provideEndDateData(): array
    {
        return [
            'Monday to Monday' => [
                '2019-08-05',
                '2019-08-05',
                '2019-08-05',
                0,
                null,
            ],
            'Monday to Tuesday' => [
                '2019-08-05',
                '2019-08-06',
                '2019-08-06',
                1,
                null,
            ],
            'Monday to Friday'  => [
                '2019-08-05',
                '2019-08-09',
                '2019-08-09',
                4,
                null,
            ],
            'Monday to Friday of next week'  => [
                '2019-08-05',
                '2019-08-16',
                '2019-08-16',
                9,
                null,
            ],
            'End date "in the past" provides negative duration' => [
                '2019-08-05',
                '2019-08-01',
                '2019-08-01',
                -2,
                'Inconsistent TimePeriod: end date 2019-08-01 is lesser than start date 2019-08-05.',
            ],
        ];
    }

    public function testCreationFromEndDateWithNullValues(): void
    {
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $logger->expects(self::never())->method('error');

        $a_date = (new \DateTimeImmutable('2019-08-05'))->getTimestamp();

        $time_period = TimePeriodWithoutWeekEnd::buildFromEndDate(
            null,
            $a_date,
            $logger
        );
        self::assertNull($time_period->getStartDate());
        self::assertNull($time_period->getDuration());
        self::assertEquals($a_date, $time_period->getEndDate());

        $time_period = TimePeriodWithoutWeekEnd::buildFromEndDate(
            $a_date,
            null,
            $logger
        );
        self::assertNull($time_period->getEndDate());
        self::assertNull($time_period->getDuration());
        self::assertEquals($a_date, $time_period->getStartDate());
    }
}
