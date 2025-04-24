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
use SimpleXMLElement;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class HiddenFieldsetsFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private HiddenFieldsetsDao&MockObject $hidden_fieldsets_dao;

    private HiddenFieldsetsFactory $hidden_fieldsets_factory;

    private HiddenFieldsetsRetriever&MockObject $hidden_fieldsets_retriever;

    protected function setUp(): void
    {
        $this->hidden_fieldsets_dao       = $this->createMock(HiddenFieldsetsDao::class);
        $this->hidden_fieldsets_retriever = $this->createMock(HiddenFieldsetsRetriever::class);

        $this->hidden_fieldsets_factory = new HiddenFieldsetsFactory(
            $this->hidden_fieldsets_dao,
            $this->hidden_fieldsets_retriever
        );
    }

    public function testLoadPostActionsReturnsASinglePostAction(): void
    {
        $transition = new \Transition(null, null, null, ListStaticValueBuilder::aStaticValue('field')->build());

        $expected_post_action = new HiddenFieldsets($transition, 0, []);

        $this->hidden_fieldsets_retriever
            ->method('getHiddenFieldsets')
            ->with($transition)
            ->willReturn($expected_post_action);

        $result = $this->hidden_fieldsets_factory->loadPostActions($transition);
        $this->assertEquals([$expected_post_action], $result);
    }

    public function testLoadPostActionsReturnsEmptyArray(): void
    {
        $transition = new \Transition(null, null, null, ListStaticValueBuilder::aStaticValue('field')->build());
        $this->hidden_fieldsets_retriever
            ->method('getHiddenFieldsets')
            ->with($transition)
            ->willThrowException(new NoHiddenFieldsetsPostActionException());

        $result = $this->hidden_fieldsets_factory->loadPostActions($transition);
        $this->assertEquals([], $result);
    }

    public function testItImportsActionFromXML(): void
    {
        $xml_content = <<<XML
            <postaction_hidden_fieldsets>
                <fieldset_id REF="F1"/>
                <fieldset_id REF="F2"/>
            </postaction_hidden_fieldsets>
XML;
        $xml         = new SimpleXMLElement($xml_content);

        $fieldset_01 = $this->createMock(\Tracker_FormElement_Container_Fieldset::class);
        $fieldset_02 = $this->createMock(\Tracker_FormElement_Container_Fieldset::class);

        $fieldset_01->method('getID')->willReturn(0);
        $fieldset_02->method('getID')->willReturn(0);

        $mapping = [
            'F1' => $fieldset_01,
            'F2' => $fieldset_02,
        ];

        $transition = $this->createMock(\Transition::class);

        $action = $this->hidden_fieldsets_factory->getInstanceFromXML($xml, $mapping, $transition);

        $this->assertInstanceOf(HiddenFieldsets::class, $action);
        $this->assertCount(2, $action->getFieldsets());
    }

    public function testItSkipsNonExistingFieldsDuringXMLImport(): void
    {
        $xml_content = <<<XML
            <postaction_hidden_fieldsets>
                <fieldset_id REF="F1"/>
                <fieldset_id REF="F2"/>
            </postaction_hidden_fieldsets>
XML;
        $xml         = new SimpleXMLElement($xml_content);

        $fieldset_01 = $this->createMock(\Tracker_FormElement_Container_Fieldset::class);
        $fieldset_01->method('getID')->willReturn(0);

        $mapping = [
            'F1' => $fieldset_01,
        ];

        $transition = $this->createMock(\Transition::class);

        $action = $this->hidden_fieldsets_factory->getInstanceFromXML($xml, $mapping, $transition);

        $this->assertInstanceOf(HiddenFieldsets::class, $action);
        $this->assertCount(1, $action->getFieldsets());
    }
}
