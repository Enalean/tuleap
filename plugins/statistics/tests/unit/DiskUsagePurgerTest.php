<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Statistics;

use PHPUnit\Framework\TestCase;
use Statistics_DiskUsageDao;
use Statistics_DiskUsagePurger;

class DiskUsagePurgerTest extends TestCase
{
    /** @var Statistics_DiskUsagePurger */
    private $disk_data_purger;

    public function setUp(): void
    {
        $this->disk_data_purger = new Statistics_DiskUsagePurger(new Statistics_DiskUsageDao(), new \Psr\Log\NullLogger());
    }

    public function testItReturnsFirstDayOfEachMonthsBetweenTwoDates()
    {
        $dates = $this->disk_data_purger->getFirstDayOfEachMonthsBetweenTwoDates('2014-01-01 00:00:00', '2014-12-31 00:00:00');
        $this->assertEquals(
            [
                '2014-01-01 00:00:00',
                '2014-02-01 00:00:00',
                '2014-03-01 00:00:00',
                '2014-04-01 00:00:00',
                '2014-05-01 00:00:00',
                '2014-06-01 00:00:00',
                '2014-07-01 00:00:00',
                '2014-08-01 00:00:00',
                '2014-09-01 00:00:00',
                '2014-10-01 00:00:00',
                '2014-11-01 00:00:00',
                '2014-12-01 00:00:00',
            ],
            $dates
        );

        $dates = $this->disk_data_purger->getFirstDayOfEachMonthsBetweenTwoDates('2014-01-08 00:00:00', '2014-11-22 00:00:00');
        $this->assertEquals(
            [
                '2014-01-01 00:00:00',
                '2014-02-01 00:00:00',
                '2014-03-01 00:00:00',
                '2014-04-01 00:00:00',
                '2014-05-01 00:00:00',
                '2014-06-01 00:00:00',
                '2014-07-01 00:00:00',
                '2014-08-01 00:00:00',
                '2014-09-01 00:00:00',
                '2014-10-01 00:00:00',
                '2014-11-01 00:00:00',
            ],
            $dates
        );
    }

    public function testItReturnsFalseIfDateMinIsGreaterThanEqualDateMax()
    {
        $dates = $this->disk_data_purger->getFirstDayOfEachMonthsBetweenTwoDates('2015-01-01 00:00:00', '2015-01-01 00:00:00');
        $this->assertFalse($dates);

        $dates = $this->disk_data_purger->getFirstDayOfEachMonthsBetweenTwoDates('2015-01-01 00:00:00', '2014-12-01 00:00:00');
        $this->assertFalse($dates);

        $dates = $this->disk_data_purger->getFirstDayOfEachWeeksBetweenTwoDates('2015-01-01 00:00:00', '2014-12-01 00:00:00');
        $this->assertFalse($dates);

        $dates = $this->disk_data_purger->getFirstDayOfEachWeeksBetweenTwoDates('2015-01-01 00:00:00', '2014-12-01 00:00:00');
        $this->assertFalse($dates);
    }

    public function testItReturnsFirstDayOfEachWeeksBetweenTwoDates()
    {
        $dates = $this->disk_data_purger->getFirstDayOfEachWeeksBetweenTwoDates('2015-02-02 00:00:00', '2015-03-01 00:00:00');
        $this->assertEquals(
            [
                '2015-02-02 00:00:00',
                '2015-02-09 00:00:00',
                '2015-02-16 00:00:00',
                '2015-02-23 00:00:00',
            ],
            $dates
        );

        $dates = $this->disk_data_purger->getFirstDayOfEachWeeksBetweenTwoDates('2015-02-03 00:00:00', '2015-02-04 00:00:00');
        $this->assertEquals(
            [
                '2015-02-02 00:00:00',
            ],
            $dates
        );
    }
}
