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

namespace Tuleap\Tracker\FormElement\Field\Date;

require_once __DIR__ . '/../../../../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CSVFormatterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var CSVFormatter */
    private $formatter;
    /** @var \Mockery\MockInterface */
    private $user;
    /** @var int */
    private $timestamp = 1540456782;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatter = new CSVFormatter();
        $this->user      = Mockery::mock(\PFUser::class);
    }

    public function testFormatDateForCSVWithMonthFirst()
    {
        $this->user->shouldReceive('getPreference')->withArgs(['user_csv_dateformat'])->andReturn('month_day_year');

        $result = $this->formatter->formatDateForCSVForUser($this->user, $this->timestamp, false);

        $this->assertEquals('10/25/2018', $result);
    }

    public function testFormatDateForCSVWithDayFirst()
    {
        $this->user->shouldReceive('getPreference')->withArgs(['user_csv_dateformat'])->andReturn('day_month_year');

        $result = $this->formatter->formatDateForCSVForUser($this->user, $this->timestamp, false);

        $this->assertEquals('25/10/2018', $result);
    }

    public function testFormatDateForCSVWithDayAndTime()
    {
        $this->user->shouldReceive('getPreference')->andReturn('day_month_year');

        $result = $this->formatter->formatDateForCSVForUser($this->user, $this->timestamp, true);

        $this->assertEquals('25/10/2018 10:39', $result);
    }

    public function testFormatDateForCSVWithMonthAndTime()
    {
        $this->user->shouldReceive('getPreference')->andReturn('month_day_year');

        $result = $this->formatter->formatDateForCSVForUser($this->user, $this->timestamp, true);

        $this->assertEquals('10/25/2018 10:39', $result);
    }

    public function testFormatDateForCSVWithDefault()
    {
        $this->user->shouldReceive('getPreference');

        $result = $this->formatter->formatDateForCSVForUser($this->user, $this->timestamp, false);

        $this->assertEquals('10/25/2018', $result);
    }
}
