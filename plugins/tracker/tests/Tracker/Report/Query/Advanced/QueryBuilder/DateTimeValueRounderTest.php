<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use TuleapTestCase;

require_once __DIR__.'/../../../../../bootstrap.php';

class DateTimeValueRounderTest extends TuleapTestCase
{
    private $previous_timezone;
    /** @var DateTimeValueRounder */
    private $date_time_value_rounder;

    public function setUp()
    {
        parent::setUp();

        $this->previous_timezone = date_default_timezone_get();
        date_default_timezone_set('UTC');
        $this->date_time_value_rounder = new DateTimeValueRounder();
    }

    public function tearDown()
    {
        date_default_timezone_set($this->previous_timezone);

        parent::tearDown();
    }

    public function itReturnsAFlooredTimestampFromADateString()
    {
        $timestamp = $this->date_time_value_rounder->getFlooredTimestampFromDate('2017-01-24');

        $timestamp_at_midnight = 1485216000;

        $this->assertEqual($timestamp, $timestamp_at_midnight);
    }

    public function itReturnsAFlooredTimestampFromADateTimeString()
    {
        $timestamp = $this->date_time_value_rounder->getFlooredTimestampFromDateTime('2017-01-12 01:15');

        $timestamp_at_one_fifteen = 1484183700;

        $this->assertEqual($timestamp, $timestamp_at_one_fifteen);
    }

    public function itReturnsATimestampFlooredToTheDayFromADateString()
    {
        $timestamp = $this->date_time_value_rounder->getFlooredTimestampFromDateTime('2013-06-19');

        $timestamp_at_midnight = 1371600000;

        $this->assertEqual($timestamp, $timestamp_at_midnight);
    }

    public function itReturnsACeiledTimestampFromADateString()
    {
        $timestamp = $this->date_time_value_rounder->getCeiledTimestampFromDate('2017-03-13');

        $timestamp_at_twenty_three_fifty_nine_fifty_nine = 1489449599;

        $this->assertEqual($timestamp, $timestamp_at_twenty_three_fifty_nine_fifty_nine);
    }

    public function itReturnsACeiledTimestampFromADatetimeString()
    {
        $timestamp = $this->date_time_value_rounder->getCeiledTimestampFromDateTime('2016-12-23 21:39');

        $timestamp_at_twenty_one_thirty_nine_fifty_nine = 1482529199;

        $this->assertEqual($timestamp, $timestamp_at_twenty_one_thirty_nine_fifty_nine);
    }

    public function itReturnsATimestampCeiledToTheDayFromADateString()
    {
        $timestamp = $this->date_time_value_rounder->getCeiledTimestampFromDateTime('2010-09-23');

        $timestamp_at_twenty_three_fifty_nine_fifty_nine = 1285286399;

        $this->assertEqual($timestamp, $timestamp_at_twenty_three_fifty_nine_fifty_nine);
    }
}
