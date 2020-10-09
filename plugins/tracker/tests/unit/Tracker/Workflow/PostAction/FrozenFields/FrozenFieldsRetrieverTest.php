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

namespace Tuleap\Tracker\Workflow\PostAction\FrozenFields;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_FormElementFactory;

final class FrozenFieldsRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var Mockery\MockInterface */
    private $frozen_dao;
    /** @var FrozenFieldsRetriever */
    private $frozen_retriever;

    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;
    private $workflow_id;
    private $workflow;

    protected function setUp(): void
    {
        $this->frozen_dao           = Mockery::mock(FrozenFieldsDao::class);
        $this->form_element_factory = Mockery::mock(\Tracker_FormElementFactory::class);
        $this->workflow_id          = '112';
        $this->workflow             = Mockery::mock(\Workflow::class, ['getId' => $this->workflow_id]);

        $this->frozen_retriever = new FrozenFieldsRetriever(
            $this->frozen_dao,
            $this->form_element_factory
        );
    }

    public function testGetFrozenFieldsReturnsASinglePostAction()
    {
        $postaction_id = 72;
        $transition_id = '97';

        $this->frozen_dao->shouldReceive('searchByWorkflow')->with($this->workflow)->andReturn([
            ['transition_id' => (int) $transition_id, 'postaction_id' => $postaction_id, 'field_id' => 331],
            ['transition_id' => (int) $transition_id, 'postaction_id' => $postaction_id, 'field_id' => 651],
            ['transition_id' => (int) $transition_id, 'postaction_id' => $postaction_id, 'field_id' => 987],
        ]);

        $int_field    = Mockery::mock(\Tracker_FormElement_Field_Integer::class);
        $float_field  = Mockery::mock(\Tracker_FormElement_Field_Float::class);
        $string_field = Mockery::mock(\Tracker_FormElement_Field_String::class);

        $this->form_element_factory->shouldReceive('getFieldById')->with(331)->andReturn($int_field);
        $this->form_element_factory->shouldReceive('getFieldById')->with(651)->andReturn($float_field);
        $this->form_element_factory->shouldReceive('getFieldById')->with(987)->andReturn($string_field);

        $transition = new \Transition($transition_id, $this->workflow_id, null, null);
        $transition->setWorkflow($this->workflow);
        $expected_post_action = new FrozenFields($transition, $postaction_id, [$int_field, $float_field, $string_field]);

        $result = $this->frozen_retriever->getFrozenFields($transition);
        $this->assertEquals($expected_post_action, $result);
    }

    public function testGetFrozenFieldsThrowsWhenNoPostAction()
    {
        $this->frozen_dao->shouldReceive('searchByWorkflow')->andReturn([]);

        $transition = new \Transition('97', $this->workflow_id, null, null);
        $transition->setWorkflow($this->workflow);

        $this->expectException(NoFrozenFieldsPostActionException::class);
        $this->frozen_retriever->getFrozenFields($transition);
    }

    public function testGetFrozenFieldsFromSeveralTransitionsReturnsASinglePostAction()
    {
        $postaction_id = 72;
        $transition_id = '97';

        $this->frozen_dao->shouldReceive('searchByWorkflow')->with($this->workflow)->andReturn([
            ['transition_id' => (int) $transition_id, 'postaction_id' => $postaction_id, 'field_id' => 331],
            ['transition_id' => 98, 'postaction_id' => $postaction_id, 'field_id' => 651],
            ['transition_id' => 99, 'postaction_id' => $postaction_id, 'field_id' => 987],
        ]);

        $int_field    = Mockery::mock(\Tracker_FormElement_Field_Integer::class);

        $this->form_element_factory->shouldReceive('getFieldById')->with(331)->andReturn($int_field);

        $transition = new \Transition($transition_id, $this->workflow_id, null, null);
        $transition->setWorkflow($this->workflow);
        $expected_post_action = new FrozenFields($transition, $postaction_id, [$int_field]);

        $result = $this->frozen_retriever->getFrozenFields($transition);
        $this->assertEquals($expected_post_action, $result);
    }
}
