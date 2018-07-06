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

namespace Tuleap\AgileDashboard\Semantic;

use AgileDashBoard_Semantic_InitialEffort;
use AgileDashboard_Semantic_InitialEffortFactory;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_FormElementFactory;

require_once __DIR__ . '/../../bootstrap.php';

class MoveSemanticCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var MoveSemanticChecker
     */
    private $checker;

    public function setUp()
    {
        parent::setUp();

        $this->initial_effort_factory = Mockery::spy(AgileDashboard_Semantic_InitialEffortFactory::class);
        $this->form_element_factory   = Mockery::spy(Tracker_FormElementFactory::class);

        $this->source_tracker = Mockery::mock(Tracker::class);
        $this->target_tracker = Mockery::mock(Tracker::class);

        $this->source_initial_effort_field = Mockery::mock(\Tracker_FormElement_Field::class);
        $this->target_initial_effort_field = Mockery::mock(\Tracker_FormElement_Field::class);

        $this->source_semantic = Mockery::mock(AgileDashBoard_Semantic_InitialEffort::class);
        $this->target_semantic = Mockery::mock(AgileDashBoard_Semantic_InitialEffort::class);

        $this->checker = new MoveSemanticChecker(
            $this->initial_effort_factory,
            $this->form_element_factory
        );
    }

    public function testSemanticAreAlignedIfBothTrackersHaveInitialEffortSemanticAndFieldHaveTheSameType()
    {
        $this->source_semantic->shouldReceive('getField')->andReturn($this->source_initial_effort_field);
        $this->target_semantic->shouldReceive('getField')->andReturn($this->target_initial_effort_field);

        $this->initial_effort_factory->shouldReceive('getByTracker')->with($this->source_tracker)->andReturn($this->source_semantic);
        $this->initial_effort_factory->shouldReceive('getByTracker')->with($this->target_tracker)->andReturn($this->target_semantic);

        $this->form_element_factory->shouldReceive('getType')->with($this->source_initial_effort_field)->andReturn('int');
        $this->form_element_factory->shouldReceive('getType')->with($this->target_initial_effort_field)->andReturn('int');

        $this->assertTrue($this->checker->areSemanticsAligned($this->source_tracker, $this->target_tracker));
    }

    public function testSemanticAreNotAlignedIfBothTrackersHaveInitialEffortSemanticAndFieldDontHaveTheSameType()
    {
        $this->source_semantic->shouldReceive('getField')->andReturn($this->source_initial_effort_field);
        $this->target_semantic->shouldReceive('getField')->andReturn($this->target_initial_effort_field);

        $this->initial_effort_factory->shouldReceive('getByTracker')->with($this->source_tracker)->andReturn($this->source_semantic);
        $this->initial_effort_factory->shouldReceive('getByTracker')->with($this->target_tracker)->andReturn($this->target_semantic);

        $this->form_element_factory->shouldReceive('getType')->with($this->source_initial_effort_field)->andReturn('float');
        $this->form_element_factory->shouldReceive('getType')->with($this->target_initial_effort_field)->andReturn('int');

        $this->assertFalse($this->checker->areSemanticsAligned($this->source_tracker, $this->target_tracker));
    }

    public function testSemanticAreNotAlignedIfBothTrackersDoNotHaveTheSemanticDefined()
    {
        $this->source_semantic->shouldReceive('getField')->andReturn(null);
        $this->target_semantic->shouldReceive('getField')->andReturn(null);

        $this->initial_effort_factory->shouldReceive('getByTracker')->with($this->source_tracker)->andReturn($this->source_semantic);
        $this->initial_effort_factory->shouldReceive('getByTracker')->with($this->target_tracker)->andReturn($this->target_semantic);

        $this->assertFalse($this->checker->areSemanticsAligned($this->source_tracker, $this->target_tracker));
    }

    public function testSemanticAreNotAlignedIfOneTrackersDoesNotHaveTheSemanticDefined()
    {
        $this->source_semantic->shouldReceive('getField')->andReturn($this->source_initial_effort_field);
        $this->target_semantic->shouldReceive('getField')->andReturn(null);

        $this->initial_effort_factory->shouldReceive('getByTracker')->with($this->source_tracker)->andReturn($this->source_semantic);
        $this->initial_effort_factory->shouldReceive('getByTracker')->with($this->target_tracker)->andReturn($this->target_semantic);

        $this->assertFalse($this->checker->areSemanticsAligned($this->source_tracker, $this->target_tracker));
    }
}
