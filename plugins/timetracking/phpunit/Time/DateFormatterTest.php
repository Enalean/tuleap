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

require_once __DIR__ . '/../bootstrap.php';

class DateFormatterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var DateFormatter
     */
    private $formatter;

    public function setUp(): void
    {
        parent::setUp();

        $this->formatter = new DateFormatter();
    }

    public function testItAlwaysReturnsTwoCharsForHoursAndForMinutes()
    {
        $minutes = 0;
        $this->assertEquals("00:00", $this->formatter->formatMinutes($minutes));

        $minutes = 1;
        $this->assertEquals("00:01", $this->formatter->formatMinutes($minutes));

        $minutes = 59;
        $this->assertEquals("00:59", $this->formatter->formatMinutes($minutes));

        $minutes = 61;
        $this->assertEquals("01:01", $this->formatter->formatMinutes($minutes));

        $minutes = 119;
        $this->assertEquals("01:59", $this->formatter->formatMinutes($minutes));

        $minutes = 601;
        $this->assertEquals("10:01", $this->formatter->formatMinutes($minutes));

        $minutes = 659;
        $this->assertEquals("10:59", $this->formatter->formatMinutes($minutes));
    }

    public function testItCanReturnsMoreThan24Hours()
    {
        $minutes = 26 * 60 + 59;
        $this->assertEquals("26:59", $this->formatter->formatMinutes($minutes));
    }
}
