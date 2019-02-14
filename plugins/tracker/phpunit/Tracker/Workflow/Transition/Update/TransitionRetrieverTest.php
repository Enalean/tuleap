<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow\Transition\Update;

require_once __DIR__ . '/../../../../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Workflow\Transition\NoSiblingTransitionException;

class TransitionRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var TransitionRetriever */
    private $transition_retriever;
    /** @var Mockery\MockInterface */
    private $transition_dao;
    /** @var Mockery\MockInterface */
    private $transition_factory;

    protected function setUp(): void
    {
        $this->transition_dao       = Mockery::mock(\Workflow_TransitionDao::class);
        $this->transition_factory   = Mockery::mock(\TransitionFactory::class);
        $this->transition_retriever = new TransitionRetriever($this->transition_dao, $this->transition_factory);
    }

    public function testGetSiblingTransitionsReturnsTransitionCollection()
    {
        $workflow_id       = 38;
        $transition_id     = 81;
        $to_id             = 239;
        $first_sibling_id  = 95;
        $second_sibling_id = 105;

        $first_row = ['transition_id' => $first_sibling_id];
        $second_row = ['transition_id' => $second_sibling_id];
        $this->transition_dao
            ->shouldReceive('searchSiblings')
            ->with($workflow_id, $to_id, $transition_id)
            ->andReturn([$first_row, $second_row]);

        $first_sibling  = $this->buildTransition($first_sibling_id, $workflow_id);
        $second_sibling = $this->buildTransition($second_sibling_id, $workflow_id);
        $this->transition_factory
            ->shouldReceive('getInstanceFromRow')
            ->with($first_row)
            ->andReturn($first_sibling);
        $this->transition_factory
            ->shouldReceive('getInstanceFromRow')
            ->with($second_row)
            ->andReturn($second_sibling);

        $transition = $this->buildTransitionWithToId($transition_id, $workflow_id, $to_id);

        $result = $this->transition_retriever->getSiblingTransitions($transition);

        $expected = new TransitionCollection($first_sibling, $second_sibling);
        $this->assertEquals($expected, $result);
    }

    public function testGetSiblingTransitionsThrowsWhenNoSibling()
    {
        $this->transition_dao->shouldReceive('searchSiblings')->andReturnFalse();
        $transition = $this->buildTransitionWithToId(19, 53, 483);

        $this->expectException(NoSiblingTransitionException::class);

        $this->transition_retriever->getSiblingTransitions($transition);
    }

    public function testGetFirstSiblingTransitionChoosesFirstTransitionThatIsNotFromNewArtifact()
    {
        $workflow_id           = 45;
        $transition_id         = 79;
        $to_id                 = 656;
        $sibling_transition_id = 76;
        $sibling_row           = ['transition_id' => $sibling_transition_id, 'from_id' => 871, 'to_id' => $to_id];
        $sibling_from_new_row  = ['transition_id' => 81, 'from_id' => '0', 'to_id' => $to_id];
        $this->transition_dao
            ->shouldReceive('searchSiblings')
            ->with($workflow_id, $to_id, $transition_id)
            ->andReturn([$sibling_from_new_row, $sibling_row]);

        $sibling_transition = $this->buildTransition($sibling_transition_id, $workflow_id);
        $this->transition_factory
            ->shouldReceive('getInstanceFromRow')
            ->with($sibling_row)
            ->andReturn($sibling_transition);

        $transition = $this->buildTransitionWithToId($transition_id, $workflow_id, $to_id);

        $result = $this->transition_retriever->getFirstSiblingTransition($transition);

        $this->assertEquals($sibling_transition, $result);
    }

    public function testGetFirstSiblingTransitionChoosesTransitionFromNewArtifactWhenItsTheOnlyOne()
    {
        $workflow_id           = 45;
        $transition_id         = 79;
        $to_id                 = 656;
        $sibling_transition_id = 81;
        $sibling_from_new_row  = ['transition_id' => $sibling_transition_id, 'from_id' => '0', 'to_id' => $to_id];
        $this->transition_dao
            ->shouldReceive('searchSiblings')
            ->with($workflow_id, $to_id, $transition_id)
            ->andReturn([$sibling_from_new_row]);

        $sibling_transition = $this->buildTransition($sibling_transition_id, $workflow_id);
        $this->transition_factory
            ->shouldReceive('getInstanceFromRow')
            ->with($sibling_from_new_row)
            ->andReturn($sibling_transition);

        $transition = $this->buildTransitionWithToId($transition_id, $workflow_id, $to_id);

        $result = $this->transition_retriever->getFirstSiblingTransition($transition);

        $this->assertEquals($sibling_transition, $result);
    }

    public function testGetFirstSiblingTransitionThrowsWhenNoSiblingTransition()
    {
        $this->transition_dao->shouldReceive('searchSiblings')->andReturn([]);
        $transition = $this->buildTransitionWithToId(16, 17, 163);

        $this->expectException(NoSiblingTransitionException::class);

        $this->transition_retriever->getFirstSiblingTransition($transition);
    }

    public function testGetFirstSiblingTransitionThrowsWhenDBError()
    {
        $this->transition_dao->shouldReceive('searchSiblings')->andReturnFalse();
        $transition = $this->buildTransitionWithToId(29, 52, 748);

        $this->expectException(NoSiblingTransitionException::class);

        $this->transition_retriever->getFirstSiblingTransition($transition);
    }

    private function buildTransition(int $transition_id, int $workflow_id): \Transition
    {
        $from = Mockery::mock(\Tracker_FormElement_Field_List_Value::class);
        $to   = Mockery::mock(\Tracker_FormElement_Field_List_Value::class);
        return new \Transition($transition_id, $workflow_id, $from, $to);
    }

    private function buildTransitionWithToId(int $transition_id, int $workflow_id, int $to_id): \Transition
    {
        $from = Mockery::mock(\Tracker_FormElement_Field_List_Value::class);
        $to   = Mockery::mock(\Tracker_FormElement_Field_List_Value::class)
            ->shouldReceive('getId')
            ->andReturn($to_id)
            ->getMock();
        return new \Transition($transition_id, $workflow_id, $from, $to);
    }
}
