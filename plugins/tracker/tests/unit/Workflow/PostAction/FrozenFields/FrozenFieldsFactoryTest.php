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
use SimpleXMLElement;
use Tuleap\Tracker\Test\Builders\Fields\FloatFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FrozenFieldsFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private FrozenFieldsDao&MockObject $frozen_dao;

    private FrozenFieldsFactory $frozen_fields_factory;

    private FrozenFieldsRetriever&MockObject $frozen_fields_retriever;

    protected function setUp(): void
    {
        $this->frozen_dao              = $this->createMock(FrozenFieldsDao::class);
        $this->frozen_fields_retriever = $this->createMock(FrozenFieldsRetriever::class);

        $this->frozen_fields_factory = new FrozenFieldsFactory(
            $this->frozen_dao,
            $this->frozen_fields_retriever
        );
    }

    public function testLoadPostActionsReturnsASinglePostAction(): void
    {
        $transition           = new \Transition(
            null,
            null,
            null,
            ListStaticValueBuilder::aStaticValue('field')->build()
        );
        $expected_post_action = new FrozenFields($transition, 0, []);
        $this->frozen_fields_retriever->method('getFrozenFields')->with($transition)->willReturn(
            $expected_post_action
        );

        $result = $this->frozen_fields_factory->loadPostActions($transition);
        $this->assertEquals([$expected_post_action], $result);
    }

    public function testLoadPostActionsReturnsEmptyArray(): void
    {
        $this->frozen_fields_retriever->method('getFrozenFields')->willThrowException(new NoFrozenFieldsPostActionException());

        $transition = new \Transition(
            null,
            null,
            null,
            ListStaticValueBuilder::aStaticValue('field')->build()
        );

        $result = $this->frozen_fields_factory->loadPostActions($transition);
        $this->assertEquals([], $result);
    }

    public function testItImportsActionFromXML(): void
    {
        $xml_content = <<<XML
            <postaction_frozen_fields>
                <field_id REF="F1"/>
                <field_id REF="F2"/>
            </postaction_frozen_fields>
XML;
        $xml         = new SimpleXMLElement($xml_content);

        $int_field   = IntegerFieldBuilder::anIntField(1)->build();
        $float_field = FloatFieldBuilder::aFloatField(2)->build();

        $mapping = [
            'F1' => $int_field,
            'F2' => $float_field,
        ];

        $transition = $this->createMock(\Transition::class);

        $action = $this->frozen_fields_factory->getInstanceFromXML($xml, $mapping, $transition);

        $this->assertInstanceOf(FrozenFields::class, $action);
        $this->assertCount(2, $action->getFieldIds());
    }

    public function testItSkipsNonExistingFieldsDuringXMLImport(): void
    {
        $xml_content = <<<XML
            <postaction_frozen_fields>
                <field_id REF="F1"/>
                <field_id REF="F2"/>
            </postaction_frozen_fields>
XML;
        $xml         = new SimpleXMLElement($xml_content);

        $int_field = IntegerFieldBuilder::anIntField(1)->build();

        $mapping = [
            'F1' => $int_field,
        ];

        $transition = $this->createMock(\Transition::class);

        $action = $this->frozen_fields_factory->getInstanceFromXML($xml, $mapping, $transition);

        $this->assertInstanceOf(FrozenFields::class, $action);
        $this->assertCount(1, $action->getFieldIds());
    }
}
