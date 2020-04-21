<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

use PHPUnit\Framework\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class TimeIntervalTest extends TestCase
{

    public function testItRefusesNegativeTimeStamps(): void
    {
        $this->assertInvalidIntervalTimestamps(-1, 1);
        $this->assertInvalidIntervalTimestamps(1, -1);
    }

    public function testItEnsuresStartIsStrictlyBeforeEnd(): void
    {
        $this->assertInvalidIntervalTimestamps(2, 1);
        $this->assertInvalidIntervalTimestamps(2, 2);
    }

    public function testItHasAStartAndAnEnd(): void
    {
        $start = 1;
        $end   = 2;

        $interval = TimeInterval::fromUnixTimestamps($start, $end);

        $this->assertEquals($start, $interval->getStartTimestamp());
        $this->assertEquals($end, $interval->getEndTimestamp());
    }

    private function assertInvalidIntervalTimestamps($start, $end): void
    {
        $this->expectException(Exception::class);
        TimeInterval::fromUnixTimestamps($start, $end);
    }
}
