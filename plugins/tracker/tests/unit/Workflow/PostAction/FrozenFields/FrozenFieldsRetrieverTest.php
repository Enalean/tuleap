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

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Workflow;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FrozenFieldsRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private FrozenFieldsDao&MockObject $frozen_dao;
    private FrozenFieldsRetriever $frozen_retriever;

    private Tracker_FormElementFactory&MockObject $form_element_factory;
    private int $workflow_id = 112;
    private Workflow&MockObject $workflow;

    protected function setUp(): void
    {
        $this->frozen_dao           = $this->createMock(FrozenFieldsDao::class);
        $this->form_element_factory = $this->createMock(\Tracker_FormElementFactory::class);
        $this->workflow             = $this->createMock(\Workflow::class);

        $this->workflow->method('getId')->willReturn($this->workflow_id);

        $this->frozen_retriever = new FrozenFieldsRetriever(
            $this->frozen_dao,
            $this->form_element_factory
        );
    }

    public function testGetFrozenFieldsReturnsASinglePostAction(): void
    {
        $postaction_id = 72;
        $transition_id = '97';

        $this->frozen_dao->method('searchByWorkflow')->with($this->workflow)->willReturn([
            ['transition_id' => (int) $transition_id, 'postaction_id' => $postaction_id, 'field_id' => 331],
            ['transition_id' => (int) $transition_id, 'postaction_id' => $postaction_id, 'field_id' => 651],
            ['transition_id' => (int) $transition_id, 'postaction_id' => $postaction_id, 'field_id' => 987],
        ]);

        $int_field    = $this->createMock(\Tracker_FormElement_Field_Integer::class);
        $float_field  = $this->createMock(\Tracker_FormElement_Field_Float::class);
        $string_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\String\StringField::class);

        $this->form_element_factory->method('getFieldById')->willReturnCallback(
            static fn (int $id) => match ($id) {
                331 => $int_field,
                651 => $float_field,
                987 => $string_field
            }
        );

        $transition = new \Transition(
            $transition_id,
            $this->workflow_id,
            null,
            ListStaticValueBuilder::aStaticValue('field')->build()
        );
        $transition->setWorkflow($this->workflow);
        $expected_post_action = new FrozenFields($transition, $postaction_id, [$int_field, $float_field, $string_field]);

        $result = $this->frozen_retriever->getFrozenFields($transition);
        $this->assertEquals($expected_post_action, $result);
    }

    public function testGetFrozenFieldsThrowsWhenNoPostAction(): void
    {
        $this->frozen_dao->method('searchByWorkflow')->willReturn([]);

        $transition = new \Transition('97', $this->workflow_id, null, ListStaticValueBuilder::aStaticValue('field')->build());
        $transition->setWorkflow($this->workflow);

        $this->expectException(NoFrozenFieldsPostActionException::class);
        $this->frozen_retriever->getFrozenFields($transition);
    }

    public function testGetFrozenFieldsFromSeveralTransitionsReturnsASinglePostAction(): void
    {
        $postaction_id = 72;
        $transition_id = '97';

        $this->frozen_dao->method('searchByWorkflow')->with($this->workflow)->willReturn([
            ['transition_id' => (int) $transition_id, 'postaction_id' => $postaction_id, 'field_id' => 331],
            ['transition_id' => 98, 'postaction_id' => $postaction_id, 'field_id' => 651],
            ['transition_id' => 99, 'postaction_id' => $postaction_id, 'field_id' => 987],
        ]);

        $int_field = $this->createMock(\Tracker_FormElement_Field_Integer::class);

        $this->form_element_factory->method('getFieldById')->with(331)->willReturn($int_field);

        $transition = new \Transition($transition_id, $this->workflow_id, null, ListStaticValueBuilder::aStaticValue('field')->build());
        $transition->setWorkflow($this->workflow);
        $expected_post_action = new FrozenFields($transition, $postaction_id, [$int_field]);

        $result = $this->frozen_retriever->getFrozenFields($transition);
        $this->assertEquals($expected_post_action, $result);
    }
}
