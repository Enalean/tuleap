<?php
/**
 * Copyright (c) Enalean, 2017 - present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class DateTimeValueRounderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $previous_timezone;
    /** @var DateTimeValueRounder */
    private $date_time_value_rounder;

    protected function setUp(): void
    {
        $this->previous_timezone = date_default_timezone_get();
        date_default_timezone_set('UTC');
        $this->date_time_value_rounder = new DateTimeValueRounder();
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->previous_timezone);
    }

    public function testItReturnsAFlooredTimestampFromADateString(): void
    {
        $timestamp = $this->date_time_value_rounder->getFlooredTimestampFromDate('2017-01-24');

        $timestamp_at_midnight = 1485216000;

        $this->assertEquals($timestamp_at_midnight, $timestamp);
    }

    public function testItReturnsAFlooredTimestampFromADateTimeString(): void
    {
        $timestamp = $this->date_time_value_rounder->getFlooredTimestampFromDateTime('2017-01-12 01:15');

        $timestamp_at_one_fifteen = 1484183700;

        $this->assertEquals($timestamp_at_one_fifteen, $timestamp);
    }

    public function testItReturnsATimestampFlooredToTheDayFromADateString(): void
    {
        $timestamp = $this->date_time_value_rounder->getFlooredTimestampFromDateTime('2013-06-19');

        $timestamp_at_midnight = 1371600000;

        $this->assertEquals($timestamp_at_midnight, $timestamp);
    }

    public function testItReturnsACeiledTimestampFromADateString(): void
    {
        $timestamp = $this->date_time_value_rounder->getCeiledTimestampFromDate('2017-03-13');

        $timestamp_at_twenty_three_fifty_nine_fifty_nine = 1489449599;

        $this->assertEquals($timestamp_at_twenty_three_fifty_nine_fifty_nine, $timestamp);
    }

    public function testItReturnsACeiledTimestampFromADatetimeString(): void
    {
        $timestamp = $this->date_time_value_rounder->getCeiledTimestampFromDateTime('2016-12-23 21:39');

        $timestamp_at_twenty_one_thirty_nine_fifty_nine = 1482529199;

        $this->assertEquals($timestamp_at_twenty_one_thirty_nine_fifty_nine, $timestamp);
    }

    public function testItReturnsATimestampCeiledToTheDayFromADateString(): void
    {
        $timestamp = $this->date_time_value_rounder->getCeiledTimestampFromDateTime('2010-09-23');

        $timestamp_at_twenty_three_fifty_nine_fifty_nine = 1285286399;

        $this->assertEquals($timestamp_at_twenty_three_fifty_nine_fifty_nine, $timestamp);
    }
}
