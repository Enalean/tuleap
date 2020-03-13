<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Timetracking\Time;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Timetracking\Exceptions\TimeTrackingBadTimeFormatException;
use Tuleap\Timetracking\Exceptions\TimeTrackingBadDateFormatException;
use Tuleap\Timetracking\Exceptions\TimeTrackingMissingTimeException;

require_once __DIR__ . '/../bootstrap.php';

class TimeCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /*
    * TimeChecker
    */
    private $time_checker;

    public function setUp() : void
    {
        parent::setUp();

        $this->user = \Mockery::spy(\PFUser::class);
        $this->user->allows()->getId()->andReturns(102);

        $this->time_checker    = new TimeChecker();
        $this->time            = \Mockery::spy(Time::class);

        $this->artifact        = \Mockery::spy(\Tracker_Artifact::class);
        $this->artifact->shouldReceive([
            'getId'      => 200
        ]);
    }

    public function testItReturnFalseIfEqual()
    {
        $this->time->allows()->getUserId()->andReturns(102);
        $this->assertFalse($this->time_checker->doesTimeBelongsToUser($this->time, $this->user));
    }

    public function testItReturnTrueIfNotEqual()
    {
        $this->time->allows()->getUserId()->andReturns(103);
        $this->assertTrue($this->time_checker->doesTimeBelongsToUser($this->time, $this->user));
    }

    public function testItReturnTimeTrackingNoTimeExceptionIfTimeIsNull()
    {
        $this->expectException(TimeTrackingMissingTimeException::class);
        $this->time_checker->checkMandatoryTimeValue(null);
    }

    public function testItReturnNullIfGoodTimeFormat()
    {
        $this->assertNull($this->time_checker->checkMandatoryTimeValue("11:23"));
    }


    public function testItReturnTimeTrackingBadTimeFormatExceptionIfBadTimeFormatWrongSlashSeparator()
    {
        $this->expectException(TimeTrackingBadTimeFormatException::class);
        $this->time_checker->checkMandatoryTimeValue("11/23");
    }

    public function testItReturnTimeTrackingBadTimeFormatExceptionIfBadTimeFormatWrongSemicolonSeparator()
    {
        $this->expectException(TimeTrackingBadTimeFormatException::class);
        $this->time_checker->checkMandatoryTimeValue("11;23");
    }

    public function testItReturnTimeTrackingBadTimeFormatExceptionIfBadTimeFormatToLong()
    {
        $this->expectException(TimeTrackingBadTimeFormatException::class);
        $this->time_checker->checkMandatoryTimeValue("11:234");
    }

    public function testItReturnTimeTrackingBadTimeFormatExceptionIfBadTimeFormatIfLetter()
    {
        $this->expectException(TimeTrackingBadTimeFormatException::class);
        $this->time_checker->checkMandatoryTimeValue("11:f8");
    }

    public function testItReturnNullIfGoodDateFormat()
    {
        $this->assertNull($this->time_checker->checkDateFormat("2018-01-01"));
    }

    public function testItReturnTimeTrackingBadDateFormatException()
    {
        $this->expectException(TimeTrackingBadDateFormatException::class);
        $this->time_checker->checkDateFormat("toto");
    }
}
