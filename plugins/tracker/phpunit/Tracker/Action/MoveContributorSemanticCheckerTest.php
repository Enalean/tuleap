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
use Tracker;
use Tracker_FormElement_Field;
use Tracker_FormElementFactory;

require_once __DIR__ . '/../../bootstrap.php';

class MoveContributorSemanticCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var MoveContributorSemanticChecker
     */
    private $checker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->form_element_factory = Mockery::spy(Tracker_FormElementFactory::class);
        $this->checker              = new MoveContributorSemanticChecker($this->form_element_factory);

        $this->source_tracker = Mockery::mock(Tracker::class);
        $this->target_tracker = Mockery::mock(Tracker::class);

        $this->source_contributor_field = Mockery::mock(Tracker_FormElement_Field::class);
        $this->target_contributor_field = Mockery::mock(Tracker_FormElement_Field::class);
    }

    public function testItReturnsTrueIfBothSemanticsAreDefined()
    {
        $this->source_tracker->shouldReceive('getContributorField')->andReturn($this->source_contributor_field);
        $this->target_tracker->shouldReceive('getContributorField')->andReturn($this->target_contributor_field);

        $this->assertTrue($this->checker->areBothSemanticsDefined($this->source_tracker, $this->target_tracker));
    }

    public function testItReturnsFalseIfAtLeastOneSemanticsIsNotDefined()
    {
        $this->source_tracker->shouldReceive('getContributorField')->andReturn($this->source_contributor_field);
        $this->target_tracker->shouldReceive('getContributorField')->andReturn(null);

        $this->assertFalse($this->checker->areBothSemanticsDefined($this->source_tracker, $this->target_tracker));

        $this->source_tracker->shouldReceive('getContributorField')->andReturn(null);
        $this->target_tracker->shouldReceive('getContributorField')->andReturn($this->target_contributor_field);

        $this->assertFalse($this->checker->areBothSemanticsDefined($this->source_tracker, $this->target_tracker));

        $this->source_tracker->shouldReceive('getContributorField')->andReturn(null);
        $this->target_tracker->shouldReceive('getContributorField')->andReturn(null);

        $this->assertFalse($this->checker->areBothSemanticsDefined($this->source_tracker, $this->target_tracker));
    }

    public function testItReturnsTrueIfBothSemanticsFieldsHaveTheSameType()
    {
        $this->source_tracker->shouldReceive('getContributorField')->andReturn($this->source_contributor_field);
        $this->target_tracker->shouldReceive('getContributorField')->andReturn($this->target_contributor_field);

        $this->form_element_factory->shouldReceive('getType')->with($this->source_contributor_field)->andReturns('msb');
        $this->form_element_factory->shouldReceive('getType')->with($this->target_contributor_field)->andReturns('msb');

        $this->assertTrue($this->checker->doesBothSemanticFieldHaveTheSameType($this->source_tracker, $this->target_tracker));
    }

    public function testItReturnsFalseIfBothSemanticsFieldsDoesNotHaveTheSameType()
    {
        $this->source_tracker->shouldReceive('getContributorField')->andReturn($this->source_contributor_field);
        $this->target_tracker->shouldReceive('getContributorField')->andReturn($this->target_contributor_field);

        $this->form_element_factory->shouldReceive('getType')->with($this->source_contributor_field)->andReturns('sb');
        $this->form_element_factory->shouldReceive('getType')->with($this->target_contributor_field)->andReturns('msb');

        $this->assertFalse($this->checker->doesBothSemanticFieldHaveTheSameType($this->source_tracker, $this->target_tracker));
    }
}
