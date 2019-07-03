<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Semantic\Timeframe;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;

class SemanticTimeframeTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItDefaultsToHardCodedFieldNames()
    {
        $timeframe = new SemanticTimeframe(Mockery::mock(Tracker::class), null, null);

        $this->assertEquals('start_date', $timeframe->getStartDateFieldName());
        $this->assertEquals('duration', $timeframe->getDurationFieldName());
    }

    public function testItReturnsGivenStartDateFieldName()
    {
        $start_date = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $start_date->shouldReceive('getName')
                   ->once()
                   ->andReturn('my_custom_start_date');

        $timeframe = new SemanticTimeframe(Mockery::mock(Tracker::class), $start_date, null);

        $this->assertEquals('my_custom_start_date', $timeframe->getStartDateFieldName());
        $this->assertEquals('duration', $timeframe->getDurationFieldName());
    }

    public function testItReturnsGivenDurationFieldName()
    {
        $duration = Mockery::mock(\Tracker_FormElement_Field_Integer::class);
        $duration->shouldReceive('getName')
                   ->once()
                   ->andReturn('my_custom_duration');

        $timeframe = new SemanticTimeframe(Mockery::mock(Tracker::class), null, $duration);

        $this->assertEquals('start_date', $timeframe->getStartDateFieldName());
        $this->assertEquals('my_custom_duration', $timeframe->getDurationFieldName());
    }
}
