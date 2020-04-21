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

namespace Tuleap\Taskboard\REST\v1;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\AgileDashboard\RemainingEffortValueRetriever;

class RemainingEffortRepresentationBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_FormElementFactory
     */
    private $factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|RemainingEffortValueRetriever
     */
    private $retriever;
    /**
     * @var RemainingEffortRepresentationBuilder
     */
    private $builder;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\PFUser
     */
    private $user;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_Artifact
     */
    private $artifact;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker
     */
    private $tracker;

    protected function setUp(): void
    {
        $this->user     = \Mockery::mock(\PFUser::class);
        $this->artifact = \Mockery::mock(\Tracker_Artifact::class);
        $this->tracker  = \Mockery::mock(\Tracker::class);

        $this->artifact->shouldReceive('getTracker')->andReturn($this->tracker);

        $this->factory   = \Mockery::mock(\Tracker_FormElementFactory::class);
        $this->retriever = \Mockery::mock(RemainingEffortValueRetriever::class);

        $this->builder = new RemainingEffortRepresentationBuilder($this->retriever, $this->factory);
    }

    public function testItReturnsNullIfNoFieldIsDefined(): void
    {
        $this->factory
            ->shouldReceive('getNumericFieldByNameForUser')
            ->with($this->tracker, $this->user, \Tracker::REMAINING_EFFORT_FIELD_NAME)
            ->once()
            ->andReturn(null);

        $this->assertNull($this->builder->getRemainingEffort($this->user, $this->artifact));
    }

    public function testItTellsIfUserCannotUpdateTheField(): void
    {
        $field = \Mockery::mock(\Tracker_FormElement_Field_Float::class);
        $field->shouldReceive('userCanUpdate')
              ->with($this->user)
              ->andReturn(false);

        $this->factory
            ->shouldReceive('getNumericFieldByNameForUser')
            ->with($this->tracker, $this->user, \Tracker::REMAINING_EFFORT_FIELD_NAME)
            ->once()
            ->andReturn($field);

        $this->retriever
            ->shouldReceive('getRemainingEffortValue');

        $representation = $this->builder->getRemainingEffort($this->user, $this->artifact);
        $this->assertFalse($representation->can_update);
    }

    public function testItTellsIfUserCanUpdateTheField(): void
    {
        $field = \Mockery::mock(\Tracker_FormElement_Field_Float::class);
        $field->shouldReceive('userCanUpdate')
              ->with($this->user)
              ->andReturn(true);

        $this->factory
            ->shouldReceive('getNumericFieldByNameForUser')
            ->with($this->tracker, $this->user, \Tracker::REMAINING_EFFORT_FIELD_NAME)
            ->once()
            ->andReturn($field);

        $this->retriever
            ->shouldReceive('getRemainingEffortValue');

        $representation = $this->builder->getRemainingEffort($this->user, $this->artifact);
        $this->assertTrue($representation->can_update);
    }

    public function testItGivesTheFloatValueOfTheField(): void
    {
        $field = \Mockery::mock(\Tracker_FormElement_Field_Float::class);
        $field->shouldReceive('userCanUpdate')
              ->with($this->user)
              ->andReturn(true);

        $this->factory
            ->shouldReceive('getNumericFieldByNameForUser')
            ->with($this->tracker, $this->user, \Tracker::REMAINING_EFFORT_FIELD_NAME)
            ->once()
            ->andReturn($field);

        $this->retriever
            ->shouldReceive('getRemainingEffortValue')
            ->with($this->user, $this->artifact)
            ->once()
            ->andReturn('3.14');

        $representation = $this->builder->getRemainingEffort($this->user, $this->artifact);
        $this->assertEquals(3.14, $representation->value);
    }

    public function testItGivesANullValueIfValueIsNotNumeric(): void
    {
        $field = \Mockery::mock(\Tracker_FormElement_Field_Float::class);
        $field->shouldReceive('userCanUpdate')
              ->with($this->user)
              ->andReturn(true);

        $this->factory
            ->shouldReceive('getNumericFieldByNameForUser')
            ->with($this->tracker, $this->user, \Tracker::REMAINING_EFFORT_FIELD_NAME)
            ->once()
            ->andReturn($field);

        $this->retriever
            ->shouldReceive('getRemainingEffortValue')
            ->with($this->user, $this->artifact)
            ->once()
            ->andReturn('whatedver');

        $representation = $this->builder->getRemainingEffort($this->user, $this->artifact);
        $this->assertNull($representation->value);
    }
}
