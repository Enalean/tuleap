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

declare(strict_types=1);

namespace Tuleap\Tracker\Workflow\SimpleMode\State;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tuleap\Tracker\Workflow\Transition\NoTransitionForStateException;

class TransitionExtractorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var TransitionExtractor
     */
    private $extractor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extractor = new TransitionExtractor();
    }

    public function testExtractsFirstTransitionNotFromNewFromStateObject()
    {
        $transition_from_new   = Mockery::mock(\Transition::class);
        $transition_from_value = Mockery::mock(\Transition::class);

        $transition_from_new->shouldReceive('getIdFrom')->andReturn('');
        $transition_from_value->shouldReceive('getIdFrom')->andReturn('210');

        $state = new State(1, [$transition_from_new, $transition_from_value]);

        $this->assertSame(
            $transition_from_value,
            $this->extractor->extractReferenceTransitionFromState($state)
        );
    }

    public function testExtractsTransitionFromNewFromStateObjectIfThisTransitionIsTheOnlyOne()
    {
        $transition_from_new = Mockery::mock(\Transition::class);
        $transition_from_new->shouldReceive('getIdFrom')->andReturn('');

        $state = new State(1, [$transition_from_new]);

        $this->assertSame(
            $transition_from_new,
            $this->extractor->extractReferenceTransitionFromState($state)
        );
    }

    public function testThrowsAnExceptionIfNoTransition()
    {
        $state = new State(1, []);

        $this->expectException(NoTransitionForStateException::class);

        $this->extractor->extractReferenceTransitionFromState($state);
    }

    public function testRetrievesSiblingsTransitionsInState()
    {
        $value_01 = Mockery::mock(Tracker_FormElement_Field_List_Bind_StaticValue::class);
        $value_02 = Mockery::mock(Tracker_FormElement_Field_List_Bind_StaticValue::class);
        $value_03 = Mockery::mock(Tracker_FormElement_Field_List_Bind_StaticValue::class);

        $value_01->shouldReceive('getId')->andReturn(101);
        $value_02->shouldReceive('getId')->andReturn(102);
        $value_03->shouldReceive('getId')->andReturn(103);

        $transition_01 = new \Transition(1, 1, $value_01, $value_02);
        $transition_02 = new \Transition(2, 1, $value_01, $value_03);

        $state = new State(1, [$transition_01, $transition_02]);

        $this->assertSame(
            [$transition_02],
            $this->extractor->extractSiblingTransitionsFromState($state, $transition_01)
        );
    }

    public function testReturnsEmptyArrayIfNoSiblingsTransitionsInState()
    {
        $value_01 = Mockery::mock(Tracker_FormElement_Field_List_Bind_StaticValue::class);
        $value_02 = Mockery::mock(Tracker_FormElement_Field_List_Bind_StaticValue::class);

        $value_01->shouldReceive('getId')->andReturn(101);
        $value_02->shouldReceive('getId')->andReturn(102);

        $transition_01 = new \Transition(1, 1, $value_01, $value_02);

        $state = new State(1, [$transition_01]);

        $this->assertSame(
            [],
            $this->extractor->extractSiblingTransitionsFromState($state, $transition_01)
        );
    }
}
