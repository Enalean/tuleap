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

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElementFactory;
use Tuleap\Tracker\FormElement\Container\Fieldset\FieldsetContainer;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class HiddenFieldsetsRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private HiddenFieldsetsDao&MockObject $hidden_dao;

    private HiddenFieldsetsRetriever $hidden_fieldsets_retriever;

    private Tracker_FormElementFactory&MockObject $form_element_factory;
    private int $workflow_id = 112;
    private \Workflow&MockObject $workflow;

    protected function setUp(): void
    {
        $this->hidden_dao           = $this->createMock(HiddenFieldsetsDao::class);
        $this->form_element_factory = $this->createMock(\Tracker_FormElementFactory::class);
        $this->workflow             = $this->createMock(\Workflow::class);

        $this->workflow->method('getId')->willReturn($this->workflow_id);

        $this->hidden_fieldsets_retriever = new HiddenFieldsetsRetriever(
            $this->hidden_dao,
            $this->form_element_factory
        );
    }

    public function testGetHiddenFieldsetsReturnsASinglePostAction(): void
    {
        $postaction_id = 72;
        $transition_id = '97';

        $this->hidden_dao->method('searchByWorkflow')->willReturn(
            [
                ['transition_id' => (int) $transition_id, 'postaction_id' => $postaction_id, 'fieldset_id' => 331],
                ['transition_id' => (int) $transition_id, 'postaction_id' => $postaction_id, 'fieldset_id' => 651],
            ]
        );

        $fieldset_01 = $this->createMock(FieldsetContainer::class);
        $fieldset_02 = $this->createMock(FieldsetContainer::class);

        $this->form_element_factory->method('getFieldsetById')
            ->willReturnCallback(static fn (int $id) => match ($id) {
331 => $fieldset_01, 651 => $fieldset_02
            });

        $transition = new \Transition($transition_id, $this->workflow_id, null, ListStaticValueBuilder::aStaticValue('field')->build());
        $transition->setWorkflow($this->workflow);
        $expected_post_action = new HiddenFieldsets($transition, $postaction_id, [$fieldset_01, $fieldset_02]);

        $result = $this->hidden_fieldsets_retriever->getHiddenFieldsets($transition);

        $this->assertEquals($expected_post_action, $result);
    }

    public function testGetHiddenFieldsetsThrowsWhenNoPostAction(): void
    {
        $this->hidden_dao->method('searchByWorkflow')->willReturn([]);

        $transition = new \Transition('97', $this->workflow_id, null, ListStaticValueBuilder::aStaticValue('field')->build());
        $transition->setWorkflow($this->workflow);

        $this->expectException(NoHiddenFieldsetsPostActionException::class);
        $this->hidden_fieldsets_retriever->getHiddenFieldsets($transition);
    }

    public function testGetAllHiddenFieldsetsPostActionsOfAllTransitionsReturnsASinglePostAction(): void
    {
        $postaction_id = 72;
        $transition_id = '97';

        $this->hidden_dao->method('searchByWorkflow')->willReturn(
            [
                ['transition_id' => (int) $transition_id, 'postaction_id' => $postaction_id, 'fieldset_id' => 331],
                ['transition_id' => 101, 'postaction_id' => $postaction_id, 'fieldset_id' => 651],
            ]
        );

        $fieldset_01 = $this->createMock(FieldsetContainer::class);

        $this->form_element_factory->method('getFieldsetById')->with(331)->willReturn($fieldset_01);

        $transition = new \Transition($transition_id, $this->workflow_id, null, ListStaticValueBuilder::aStaticValue('field')->build());
        $transition->setWorkflow($this->workflow);
        $expected_post_action = new HiddenFieldsets($transition, $postaction_id, [$fieldset_01]);

        $result = $this->hidden_fieldsets_retriever->getHiddenFieldsets($transition);

        $this->assertEquals($expected_post_action, $result);
    }
}
