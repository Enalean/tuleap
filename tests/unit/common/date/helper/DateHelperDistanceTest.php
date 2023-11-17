<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2010. All rights reserved
 * Copyright (c) Enalean, 2017-present. All rights reserved
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

declare(strict_types=1);

namespace Tuleap\common\date;

use DateHelper;

final class DateHelperDistanceTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private int $today_at_midnight;

    protected function setUp(): void
    {
        parent::setUp();
        $this->today_at_midnight = mktime(0, 0, 0);
    }

    public function testItComputesTheTimestamp2DaysAgoAtMidnight(): void
    {
        $expected_time = strtotime('-2 days', $this->today_at_midnight);
        self::assertEquals($expected_time, DateHelper::getTimestampAtMidnight("-2 days"));
    }

    public function testItComputesTheTimestamp3DaysInTheFutureAtMidnight(): void
    {
        $expected_time = strtotime('+3 days', $this->today_at_midnight);
        self::assertEquals($expected_time, DateHelper::getTimestampAtMidnight("+3 days"));
    }
}
