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

require_once __DIR__ . '/../bootstrap.php';

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DateFormatterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var DateFormatter
     */
    private $formatter;

    #[\Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->formatter = new DateFormatter();
    }

    public function testItAlwaysReturnsTwoCharsForHoursAndForMinutes(): void
    {
        $minutes = 0;
        self::assertEquals('00:00', $this->formatter->formatMinutes($minutes));

        $minutes = 1;
        self::assertEquals('00:01', $this->formatter->formatMinutes($minutes));

        $minutes = 59;
        self::assertEquals('00:59', $this->formatter->formatMinutes($minutes));

        $minutes = 61;
        self::assertEquals('01:01', $this->formatter->formatMinutes($minutes));

        $minutes = 119;
        self::assertEquals('01:59', $this->formatter->formatMinutes($minutes));

        $minutes = 601;
        self::assertEquals('10:01', $this->formatter->formatMinutes($minutes));

        $minutes = 659;
        self::assertEquals('10:59', $this->formatter->formatMinutes($minutes));
    }

    public function testItCanReturnsMoreThan24Hours(): void
    {
        $minutes = 26 * 60 + 59;
        self::assertEquals('26:59', $this->formatter->formatMinutes($minutes));
    }
}
