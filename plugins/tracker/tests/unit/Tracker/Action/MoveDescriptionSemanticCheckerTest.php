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

namespace Tuleap\Tracker\Action;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Field;

require_once __DIR__ . '/../../bootstrap.php';

class MoveDescriptionSemanticCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var MoveStatusSemanticChecker
     */
    private $checker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->form_element_factory = Mockery::spy(\Tracker_FormElementFactory::class);
        $this->checker              = new MoveDescriptionSemanticChecker($this->form_element_factory);

        $this->source_tracker = Mockery::mock(\Tracker::class);
        $this->target_tracker = Mockery::mock(\Tracker::class);

        $this->source_description_field = Mockery::mock(Tracker_FormElement_Field::class);
        $this->target_description_field = Mockery::mock(Tracker_FormElement_Field::class);
    }

    public function testItReturnsTrueIfBothSemanticsAreDefined()
    {
        $this->source_tracker->shouldReceive('hasSemanticsDescription')->once()->andReturn(true);
        $this->target_tracker->shouldReceive('hasSemanticsDescription')->once()->andReturn(true);

        $this->assertTrue($this->checker->areBothSemanticsDefined($this->source_tracker, $this->target_tracker));
    }

    public function testItReturnsFalseIfAtLeastOneSemanticsIsNotDefined()
    {
        $this->source_tracker->shouldReceive('hasSemanticsDescription')->andReturn(false);
        $this->target_tracker->shouldReceive('hasSemanticsDescription')->andReturn(true);

        $this->assertFalse($this->checker->areBothSemanticsDefined($this->source_tracker, $this->target_tracker));

        $this->source_tracker->shouldReceive('hasSemanticsDescription')->andReturn(true);
        $this->target_tracker->shouldReceive('hasSemanticsDescription')->andReturn(false);

        $this->assertFalse($this->checker->areBothSemanticsDefined($this->source_tracker, $this->target_tracker));

        $this->source_tracker->shouldReceive('hasSemanticsDescription')->andReturn(false);
        $this->target_tracker->shouldReceive('hasSemanticsDescription')->andReturn(false);

        $this->assertFalse($this->checker->areBothSemanticsDefined($this->source_tracker, $this->target_tracker));
    }

    public function testItReturnsTrueIfBothSemanticsFieldsHaveTheSameType()
    {
        $this->source_tracker->shouldReceive('getDescriptionField')->once()->andReturn($this->source_description_field);
        $this->target_tracker->shouldReceive('getDescriptionField')->once()->andReturn($this->target_description_field);

        $this->form_element_factory->shouldReceive('getType')->with($this->source_description_field)->andReturns('text');
        $this->form_element_factory->shouldReceive('getType')->with($this->target_description_field)->andReturns('text');

        $this->assertTrue($this->checker->doesBothSemanticFieldHaveTheSameType($this->source_tracker, $this->target_tracker));
    }

    public function testItReturnsFalseIfBothSemanticsFieldsDoesNotHaveTheSameType()
    {
        $this->source_tracker->shouldReceive('getDescriptionField')->once()->andReturn($this->source_description_field);
        $this->target_tracker->shouldReceive('getDescriptionField')->once()->andReturn($this->target_description_field);

        $this->form_element_factory->shouldReceive('getType')->with($this->source_description_field)->andReturns('text');
        $this->form_element_factory->shouldReceive('getType')->with($this->target_description_field)->andReturns('string');

        $this->assertFalse($this->checker->doesBothSemanticFieldHaveTheSameType($this->source_tracker, $this->target_tracker));
    }
}
