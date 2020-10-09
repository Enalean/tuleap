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

namespace Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Container_Fieldset;
use Tracker_FormElementFactory;

class HiddenFieldsetsRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var Mockery\MockInterface */
    private $hidden_dao;

    /** @var HiddenFieldsetsRetriever */
    private $hidden_fieldsets_retriever;

    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var string
     */
    private $workflow_id;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Workflow
     */
    private $workflow;

    protected function setUp(): void
    {
        $this->hidden_dao           = Mockery::mock(HiddenFieldsetsDao::class);
        $this->form_element_factory = Mockery::mock(\Tracker_FormElementFactory::class);
        $this->workflow_id          = '112';
        $this->workflow             = Mockery::mock(\Workflow::class, ['getId' => $this->workflow_id]);

        $this->hidden_fieldsets_retriever = new HiddenFieldsetsRetriever(
            $this->hidden_dao,
            $this->form_element_factory
        );
    }

    public function testGetHiddenFieldsetsReturnsASinglePostAction()
    {
        $postaction_id = 72;
        $transition_id = '97';

        $this->hidden_dao->shouldReceive('searchByWorkflow')->andReturn(
            [
                ['transition_id' => (int) $transition_id, 'postaction_id' => $postaction_id, 'fieldset_id' => 331],
                ['transition_id' => (int) $transition_id, 'postaction_id' => $postaction_id, 'fieldset_id' => 651],
            ]
        );

        $fieldset_01 = Mockery::mock(Tracker_FormElement_Container_Fieldset::class);
        $fieldset_02 = Mockery::mock(Tracker_FormElement_Container_Fieldset::class);

        $this->form_element_factory->shouldReceive('getFieldsetById')->with(331)->andReturn($fieldset_01);
        $this->form_element_factory->shouldReceive('getFieldsetById')->with(651)->andReturn($fieldset_02);

        $transition = new \Transition($transition_id, $this->workflow_id, null, null);
        $transition->setWorkflow($this->workflow);
        $expected_post_action = new HiddenFieldsets($transition, $postaction_id, [$fieldset_01, $fieldset_02]);

        $result = $this->hidden_fieldsets_retriever->getHiddenFieldsets($transition);

        $this->assertEquals($expected_post_action, $result);
    }

    public function testGetHiddenFieldsetsThrowsWhenNoPostAction()
    {
        $this->hidden_dao->shouldReceive('searchByWorkflow')->andReturn([]);

        $transition = new \Transition('97', $this->workflow_id, null, null);
        $transition->setWorkflow($this->workflow);

        $this->expectException(NoHiddenFieldsetsPostActionException::class);
        $this->hidden_fieldsets_retriever->getHiddenFieldsets($transition);
    }

    public function testGetAllHiddenFieldsetsPostActionsOfAllTransitionsReturnsASinglePostAction()
    {
        $postaction_id = 72;
        $transition_id = '97';

        $this->hidden_dao->shouldReceive('searchByWorkflow')->andReturn(
            [
                ['transition_id' => (int) $transition_id, 'postaction_id' => $postaction_id, 'fieldset_id' => 331],
                ['transition_id' => 101, 'postaction_id' => $postaction_id, 'fieldset_id' => 651],
            ]
        );

        $fieldset_01 = Mockery::mock(Tracker_FormElement_Container_Fieldset::class);

        $this->form_element_factory->shouldReceive('getFieldsetById')->with(331)->andReturn($fieldset_01);

        $transition = new \Transition($transition_id, $this->workflow_id, null, null);
        $transition->setWorkflow($this->workflow);
        $expected_post_action = new HiddenFieldsets($transition, $postaction_id, [$fieldset_01]);

        $result = $this->hidden_fieldsets_retriever->getHiddenFieldsets($transition);

        $this->assertEquals($expected_post_action, $result);
    }
}
