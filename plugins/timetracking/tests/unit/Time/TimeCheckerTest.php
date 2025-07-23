<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use Tuleap\Timetracking\Exceptions\TimeTrackingBadDateFormatException;
use Tuleap\Timetracking\Exceptions\TimeTrackingBadTimeFormatException;
use Tuleap\Timetracking\Exceptions\TimeTrackingMissingTimeException;

require_once __DIR__ . '/../bootstrap.php';

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TimeCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private TimeChecker $time_checker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\PFUser
     */
    private $user;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Time
     */
    private $time;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tuleap\Tracker\Artifact\Artifact
     */
    private $artifact;

    #[\Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createMock(\PFUser::class);
        $this->user->method('getId')->willReturn(102);

        $this->time_checker = new TimeChecker();
        $this->time         = $this->createMock(Time::class);

        $this->artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->artifact->method('getId')->willReturn(200);
    }

    public function testItReturnFalseIfEqual(): void
    {
        $this->time->method('getUserId')->willReturn(102);
        self::assertFalse($this->time_checker->doesTimeBelongsToUser($this->time, $this->user));
    }

    public function testItReturnTrueIfNotEqual(): void
    {
        $this->time->method('getUserId')->willReturn(103);
        self::assertTrue($this->time_checker->doesTimeBelongsToUser($this->time, $this->user));
    }

    public function testItReturnTimeTrackingNoTimeExceptionIfTimeIsNull(): void
    {
        $this->expectException(TimeTrackingMissingTimeException::class);
        $this->time_checker->checkMandatoryTimeValue(null);
    }

    public function testItDoesNotThrowExceptionIfGoodTimeFormat(): void
    {
        $this->time_checker->checkMandatoryTimeValue('11:23');
        $this->expectNotToPerformAssertions();
    }

    public function testItReturnTimeTrackingBadTimeFormatExceptionIfBadTimeFormatWrongSlashSeparator(): void
    {
        $this->expectException(TimeTrackingBadTimeFormatException::class);
        $this->time_checker->checkMandatoryTimeValue('11/23');
    }

    public function testItReturnTimeTrackingBadTimeFormatExceptionIfBadTimeFormatWrongSemicolonSeparator(): void
    {
        $this->expectException(TimeTrackingBadTimeFormatException::class);
        $this->time_checker->checkMandatoryTimeValue('11;23');
    }

    public function testItReturnTimeTrackingBadTimeFormatExceptionIfBadTimeFormatToLong(): void
    {
        $this->expectException(TimeTrackingBadTimeFormatException::class);
        $this->time_checker->checkMandatoryTimeValue('11:234');
    }

    public function testItReturnTimeTrackingBadTimeFormatExceptionIfBadTimeFormatIfLetter(): void
    {
        $this->expectException(TimeTrackingBadTimeFormatException::class);
        $this->time_checker->checkMandatoryTimeValue('11:f8');
    }

    public function testItDoesNotThrowExceptionIfGoodDateFormat(): void
    {
        $this->time_checker->checkDateFormat('2018-01-01');
        $this->expectNotToPerformAssertions();
    }

    public function testItReturnTimeTrackingBadDateFormatException(): void
    {
        $this->expectException(TimeTrackingBadDateFormatException::class);
        $this->time_checker->checkDateFormat('toto');
    }
}
