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
 */

namespace Tuleap\Tracker\Semantic\Timeframe;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_Integer;
use Tracker_FormElementFactory;

final class TimeframeCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var TimeframeChecker
     */
    private $checker;

    private $form_element_factory;
    private $tracker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->form_element_factory = Mockery::mock(Tracker_FormElementFactory::class);

        $this->checker = new TimeframeChecker($this->form_element_factory);

        $this->tracker = Mockery::mock(Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturn(1);
    }

    public function testItReturnsTrueIfATimePeriodCanBeBuildForTracker()
    {
        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('isUsed')->andReturnTrue();

        $this->form_element_factory->shouldReceive('getFormElementByName')
            ->with(1, 'start_date')
            ->once()
            ->andReturn($start_date_field);

        $duration_field = Mockery::mock(Tracker_FormElement_Field_Integer::class);
        $duration_field->shouldReceive('isUsed')->andReturnTrue();

        $this->form_element_factory->shouldReceive('getFormElementByName')
            ->with(1, 'duration')
            ->once()
            ->andReturn($duration_field);

        $this->assertTrue($this->checker->isATimePeriodBuildableInTracker($this->tracker));
    }

    public function testItReturnsFalseIfStartDateFieldIsMissingInTracker()
    {
        $this->form_element_factory->shouldReceive('getFormElementByName')
            ->with(1, 'start_date')
            ->once()
            ->andReturnNull();

        $duration_field = Mockery::mock(Tracker_FormElement_Field_Integer::class);
        $duration_field->shouldReceive('isUsed')->andReturnTrue();

        $this->form_element_factory->shouldReceive('getFormElementByName')
            ->with(1, 'duration')
            ->once()
            ->andReturn($duration_field);

        $this->assertFalse($this->checker->isATimePeriodBuildableInTracker($this->tracker));
    }

    public function testItReturnsFalseIfDurationFieldIsMissingInTracker()
    {
        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('isUsed')->andReturnTrue();

        $this->form_element_factory->shouldReceive('getFormElementByName')
            ->with(1, 'start_date')
            ->once()
            ->andReturn($start_date_field);

        $this->form_element_factory->shouldReceive('getFormElementByName')
            ->with(1, 'duration')
            ->once()
            ->andReturnNull();

        $this->assertFalse($this->checker->isATimePeriodBuildableInTracker($this->tracker));
    }

    public function testItReturnsFalseIfStartDateFieldIsNotUsedInTracker()
    {
        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('isUsed')->andReturnFalse();

        $this->form_element_factory->shouldReceive('getFormElementByName')
            ->with(1, 'start_date')
            ->once()
            ->andReturn($start_date_field);

        $duration_field = Mockery::mock(Tracker_FormElement_Field_Integer::class);
        $duration_field->shouldReceive('isUsed')->andReturnTrue();

        $this->form_element_factory->shouldReceive('getFormElementByName')
            ->with(1, 'duration')
            ->once()
            ->andReturn($duration_field);

        $this->assertFalse($this->checker->isATimePeriodBuildableInTracker($this->tracker));
    }

    public function testItReturnsFalseIfDurationFieldIsNotUsedInTracker()
    {
        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('isUsed')->andReturnTrue();

        $this->form_element_factory->shouldReceive('getFormElementByName')
            ->with(1, 'start_date')
            ->once()
            ->andReturn($start_date_field);

        $duration_field = Mockery::mock(Tracker_FormElement_Field_Integer::class);
        $duration_field->shouldReceive('isUsed')->andReturnFalse();

        $this->form_element_factory->shouldReceive('getFormElementByName')
            ->with(1, 'duration')
            ->once()
            ->andReturn($duration_field);

        $this->assertFalse($this->checker->isATimePeriodBuildableInTracker($this->tracker));
    }
}
