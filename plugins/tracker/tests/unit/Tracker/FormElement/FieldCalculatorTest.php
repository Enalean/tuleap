<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class FieldCalculatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var FieldCalculator
     */
    private $field_calculator;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ComputedFieldCalculator
     */
    private $provider;

    protected function setUp(): void
    {
        $this->provider         = \Mockery::mock(ComputedFieldCalculator::class);
        $this->field_calculator = new FieldCalculator($this->provider);
    }

    public function testItComputesDirectValues(): void
    {
        $child_one = ['id' => 750, 'artifact_link_id' => 750, 'type' => 'int', 'int_value' => 5, 'parent_id' => 233];
        $child_two = ['id' => 751, 'artifact_link_id' => 751, 'type' => 'int', 'int_value' => 15, 'parent_id' => 233];

        $children = \TestHelper::arrayToDar($child_one, $child_two);

        $this->provider->shouldReceive('fetchChildrenAndManualValuesOfArtifacts')
            ->withArgs([[233], \Mockery::any(), true, 'effort', 109])->andReturn(
                [
                    'children'   => $children,
                    'manual_sum' => null
                ]
            );

        $value = $this->field_calculator->calculate([233], time(), true, 'effort', 109);
        $this->assertEquals(20, $value);
    }

    public function testItComputesTreeValues(): void
    {
        $child_one   = ['id' => 750, 'artifact_link_id' => 750, 'type' => 'int', 'int_value' => 5, 'parent_id' => 233];
        $child_two   = ['id' => 751, 'artifact_link_id' => 751, 'type' => 'int', 'int_value' => 15, 'parent_id' => 233];
        $child_three = ['id' => 766, 'artifact_link_id' => 766, 'type' => 'computed', 'parent_id' => 233];
        $child_four  = ['id' => 777, 'artifact_link_id' => 777, 'type' => 'computed', 'parent_id' => 233];

        $children = \TestHelper::arrayToDar($child_one, $child_two, $child_three, $child_four);

        $this->provider->shouldReceive('fetchChildrenAndManualValuesOfArtifacts')
            ->withArgs([[233], \Mockery::any(), true, 'effort', 109])->andReturn(
                [
                    'children'   => $children,
                    'manual_sum' => null
                ]
            );

        $child_five = ['id' => 752, 'artifact_link_id' => 752, 'type' => 'int', 'int_value' => 10, 'parent_id' => 766];
        $child_six  = ['id' => 753, 'artifact_link_id' => 753, 'type' => 'int', 'int_value' => 10, 'parent_id' => 777];

        $other_children = \TestHelper::arrayToDar($child_five, $child_six);
        $this->provider->shouldReceive('fetchChildrenAndManualValuesOfArtifacts')
            ->withArgs([[766, 777], \Mockery::any(), true, 'effort', 109])->andReturn(
                [
                    'children'   => $other_children,
                    'manual_sum' => null
                ]
            );

        $value = $this->field_calculator->calculate([233], time(), true, 'effort', 109);

        $this->assertEquals(40, $value);
    }

    public function testItDoesntMakeLoopInGraph(): void
    {
        $child_one   = ['id' => 750, 'artifact_link_id' => 750, 'type' => 'int', 'int_value' => 5, 'parent_id' => 233];
        $child_two   = ['id' => 751, 'artifact_link_id' => 751, 'type' => 'int', 'int_value' => 15, 'parent_id' => 233];
        $child_three = ['id' => 766, 'artifact_link_id' => 766, 'type' => 'computed', 'parent_id' => 233];
        $child_four  = ['id' => 777, 'artifact_link_id' => 777, 'type' => 'computed', 'parent_id' => 233];
        $children    = \TestHelper::arrayToDar($child_one, $child_two, $child_three, $child_four);
        $this->provider->shouldReceive('fetchChildrenAndManualValuesOfArtifacts')
            ->withArgs([[233], \Mockery::any(), true, 'effort', 109])->andReturn(
                [
                    'children'   => $children,
                    'manual_sum' => null
                ]
            );

        $child_five = ['id' => 752, 'artifact_link_id' => 750, 'type' => 'int', 'int_value' => 10, 'parent_id' => 766];
        $child_six  = ['id' => 753, 'artifact_link_id' => 751, 'type' => 'int', 'int_value' => 10, 'parent_id' => 777];

        $other_children = \TestHelper::arrayToDar($child_five, $child_six, $child_three, $child_four);
        $this->provider->shouldReceive('fetchChildrenAndManualValuesOfArtifacts')
            ->withArgs([[766, 777], \Mockery::any(), true, 'effort', 109])->andReturn(
                [
                    'children'   => $other_children,
                    'manual_sum' => null
                ]
            );

        $value = $this->field_calculator->calculate([233], time(), true, 'effort', 109);

        $this->assertEquals(40, $value);
    }

    /**
     * This use case highlights the case where a Release have 2 backlog elements
     * and 2 sprints. The backlog elements are also presents in the sprints backlog
     * each backlog element should be counted only once.
     */
    public function testItDoesntCountTwiceTheFinalData(): void
    {
        $child_one   = ['id' => 750, 'artifact_link_id' => 750, 'type' => 'int', 'int_value' => 5, 'parent_id' => 233];
        $child_two   = ['id' => 751, 'artifact_link_id' => 751, 'type' => 'int', 'int_value' => 15, 'parent_id' => 233];
        $child_three = ['id' => 766, 'artifact_link_id' => 766, 'type' => 'computed', 'parent_id' => 233];
        $child_four  = ['id' => 777, 'artifact_link_id' => 777, 'type' => 'computed', 'parent_id' => 233];
        $children    = \TestHelper::arrayToDar($child_one, $child_two, $child_three, $child_four);

        $this->provider->shouldReceive('fetchChildrenAndManualValuesOfArtifacts')
            ->withArgs([[233], \Mockery::any(), true, 'effort', 109])->andReturn(
                [
                    'children'   => $children,
                    'manual_sum' => null
                ]
            );

        $child_five = ['id' => 750, 'artifact_link_id' => 750, 'type' => 'int', 'int_value' => 5, 'parent_id' => 766];
        $child_six        = ['id' => 751, 'artifact_link_id' => 751, 'type' => 'int', 'int_value' => 15, 'parent_id' => 766];
        $child_seven = ['id' => 766, 'artifact_link_id' => 766, 'type' => 'computed', 'parent_id' => 777];

        $other_children    = \TestHelper::arrayToDar($child_five, $child_six, $child_seven);
        $this->provider->shouldReceive('fetchChildrenAndManualValuesOfArtifacts')
            ->withArgs([[766], \Mockery::any(), true, 'effort', 109])->andReturn(
                [
                    'children'   => $other_children,
                    'manual_sum' => null
                ]
            );

        $other_children    = \TestHelper::arrayToDar($child_five, $child_six, $child_seven);
        $this->provider->shouldReceive('fetchChildrenAndManualValuesOfArtifacts')
            ->withArgs([[766, 777], \Mockery::any(), true, 'effort', 109])->andReturn(
                [
                    'children'   => $other_children,
                    'manual_sum' => null
                ]
            );


        $value = $this->field_calculator->calculate([233], time(), true, 'effort', 109);

        $this->assertEquals(20, $value);
    }

    public function testItStopsWhenAManualValueIsSet(): void
    {
        $child_one = ['id' => 766, 'artifact_link_id' => 766, 'type' => 'computed', 'parent_id' => 233];
        $children  = \TestHelper::arrayToDar($child_one);

        $this->provider->shouldReceive('fetchChildrenAndManualValuesOfArtifacts')
            ->withArgs([[233], \Mockery::any(), true, 'effort', 109])->andReturn(
                [
                    'children'   => $children,
                    'manual_sum' => null
                ]
            );

        $child_two   = ['id' => 766, 'artifact_link_id' => 766, 'type' => 'computed', 'value' => 4, 'parent_id' => 766];
        $child_three = ['id' => 750, 'artifact_link_id' => 750, 'type' => 'int', 'int_value' => 5, 'parent_id' => 766];
        $child_four  = ['id' => 751, 'artifact_link_id' => 751, 'type' => 'int', 'int_value' => 15, 'parent_id' => 766];

        $other_children = \TestHelper::arrayToDar($child_two, $child_three, $child_four);
        $this->provider->shouldReceive('fetchChildrenAndManualValuesOfArtifacts')
            ->withArgs([[766], \Mockery::any(), true, 'effort', 109])->andReturn(
                [
                    'children'   => $other_children,
                    'manual_sum' => null
                ]
            );
        $value = $this->field_calculator->calculate([233], time(), true, 'effort', 109);

        $this->assertEquals(4, $value);
    }

    public function testItCanAddManuallySetValuesAndComputedValues(): void
    {
        $child_one = ['id' => 766, 'artifact_link_id' => 766, 'type' => 'computed', 'parent_id' => 233, 'value' => 4.7500];
        $child_two = ['id' => 777, 'artifact_link_id' => 777, 'type' => 'computed', 'parent_id' => null];
        $children  = \TestHelper::arrayToDar($child_one, $child_two);

        $this->provider->shouldReceive('fetchChildrenAndManualValuesOfArtifacts')
            ->withArgs([[233], \Mockery::any(), true, 'effort', 109])->andReturn(
                [
                    'children'   => $children,
                    'manual_sum' => null
                ]
            );

        $child_three = ['id' => 750, 'artifact_link_id' => 750, 'type' => 'float', 'float_value' => 5.2500, 'parent_id' => 777];
        $child_four  = ['id' => 751, 'artifact_link_id' => 751, 'type' => 'float', 'float_value' => 15, 'parent_id' => 777];

        $other_children = \TestHelper::arrayToDar($child_three, $child_four);
        $this->provider->shouldReceive('fetchChildrenAndManualValuesOfArtifacts')
            ->withArgs([[777], \Mockery::any(), true, 'effort', 109])->andReturn(
                [
                    'children'   => $other_children,
                    'manual_sum' => null
                ]
            );

        $value = $this->field_calculator->calculate([233], time(), true, 'effort', 109);

        $this->assertEquals(25, $value);
    }
}
