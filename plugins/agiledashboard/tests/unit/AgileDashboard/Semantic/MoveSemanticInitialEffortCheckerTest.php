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

namespace Tuleap\AgileDashboard\Semantic;

use AgileDashBoard_Semantic_InitialEffort;
use AgileDashboard_Semantic_InitialEffortFactory;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tracker;
use Tracker_FormElement_Field;
use Tracker_FormElementFactory;

class MoveSemanticInitialEffortCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var MoveSemanticInitialEffortChecker
     */
    private $checker;
    /**
     * @var Tracker_FormElementFactory&Mockery\MockInterface
     */
    private $form_element_factory;
    /**
     * @var Tracker&Mockery\MockInterface
     */
    private $source_tracker;
    /**
     * @var Tracker&Mockery\MockInterface
     */
    private $target_tracker;
    /**
     * @var Tracker_FormElement_Field&Mockery\MockInterface
     */
    private $source_initial_effort_field;
    /**
     * @var Tracker_FormElement_Field&Mockery\MockInterface
     */
    private $target_initial_effort_field;
    /**
     * @var AgileDashBoard_Semantic_InitialEffort&Mockery\MockInterface
     */
    private $source_initial_effort_semantic;
    /**
     * @var AgileDashBoard_Semantic_InitialEffort&Mockery\MockInterface
     */
    private $target_initial_effort_semantic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->form_element_factory = Mockery::spy(Tracker_FormElementFactory::class);
        $initial_effort_factory     = Mockery::spy(AgileDashboard_Semantic_InitialEffortFactory::class);
        $this->checker              = new MoveSemanticInitialEffortChecker(
            $initial_effort_factory,
            $this->form_element_factory
        );

        $this->source_tracker = Mockery::mock(Tracker::class);
        $this->target_tracker = Mockery::mock(Tracker::class);

        $this->source_initial_effort_field = Mockery::mock(Tracker_FormElement_Field::class);
        $this->target_initial_effort_field = Mockery::mock(Tracker_FormElement_Field::class);

        $this->source_initial_effort_semantic = Mockery::mock(AgileDashBoard_Semantic_InitialEffort::class);
        $this->target_initial_effort_semantic = Mockery::mock(AgileDashBoard_Semantic_InitialEffort::class);

        $initial_effort_factory
            ->shouldReceive('getByTracker')
            ->with($this->source_tracker)
            ->andReturn($this->source_initial_effort_semantic);

        $initial_effort_factory
            ->shouldReceive('getByTracker')
            ->with($this->target_tracker)
            ->andReturn($this->target_initial_effort_semantic);
    }

    public function testItReturnsTrueIfBothSemanticsAreDefined()
    {
        $this->source_initial_effort_semantic->shouldReceive('getField')->andReturn($this->source_initial_effort_field);
        $this->target_initial_effort_semantic->shouldReceive('getField')->andReturn($this->target_initial_effort_field);

        $this->assertTrue($this->checker->areBothSemanticsDefined($this->source_tracker, $this->target_tracker));
    }

    public function testItReturnsFalseIfAtLeastOneSemanticsIsNotDefined()
    {
        $this->source_initial_effort_semantic->shouldReceive('getField')->andReturn($this->source_initial_effort_field);
        $this->target_initial_effort_semantic->shouldReceive('getField')->andReturn(null);

        $this->assertFalse($this->checker->areBothSemanticsDefined($this->source_tracker, $this->target_tracker));

        $this->source_initial_effort_semantic->shouldReceive('getField')->andReturn(null);
        $this->target_initial_effort_semantic->shouldReceive('getField')->andReturn($this->target_initial_effort_field);

        $this->assertFalse($this->checker->areBothSemanticsDefined($this->source_tracker, $this->target_tracker));

        $this->source_initial_effort_semantic->shouldReceive('getField')->andReturn(null);
        $this->target_initial_effort_semantic->shouldReceive('getField')->andReturn(null);

        $this->assertFalse($this->checker->areBothSemanticsDefined($this->source_tracker, $this->target_tracker));
    }

    public function testItReturnsTrueIfBothSemanticsFieldsHaveTheSameType()
    {
        $this->source_initial_effort_semantic->shouldReceive('getField')->andReturn($this->source_initial_effort_field);
        $this->target_initial_effort_semantic->shouldReceive('getField')->andReturn($this->target_initial_effort_field);

        $this->form_element_factory->shouldReceive('getType')->with($this->source_initial_effort_field)->andReturns('int');
        $this->form_element_factory->shouldReceive('getType')->with($this->target_initial_effort_field)->andReturns('int');

        $this->assertTrue($this->checker->doesBothSemanticFieldHaveTheSameType($this->source_tracker, $this->target_tracker));
    }

    public function testItReturnsFalseIfBothSemanticsFieldsDoesNotHaveTheSameType()
    {
        $this->source_initial_effort_semantic->shouldReceive('getField')->andReturn($this->source_initial_effort_field);
        $this->target_initial_effort_semantic->shouldReceive('getField')->andReturn($this->target_initial_effort_field);

        $this->form_element_factory->shouldReceive('getType')->with($this->source_initial_effort_field)->andReturns('int');
        $this->form_element_factory->shouldReceive('getType')->with($this->target_initial_effort_field)->andReturns('float');

        $this->assertFalse($this->checker->doesBothSemanticFieldHaveTheSameType($this->source_tracker, $this->target_tracker));
    }
}
