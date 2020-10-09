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

require_once __DIR__ . '/../../../../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;

final class FrozenFieldsFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var Mockery\MockInterface */
    private $frozen_dao;

    /** @var FrozenFieldsFactory */
    private $frozen_fields_factory;

    /**
     * @var FrozenFieldsRetriever
     */
    private $frozen_fields_retriever;

    protected function setUp(): void
    {
        $this->frozen_dao              = Mockery::mock(FrozenFieldsDao::class);
        $this->frozen_fields_retriever = Mockery::mock(FrozenFieldsRetriever::class);

        $this->frozen_fields_factory = new FrozenFieldsFactory(
            $this->frozen_dao,
            $this->frozen_fields_retriever
        );
    }

    public function testLoadPostActionsReturnsASinglePostAction()
    {
        $transition  = new \Transition(null, null, null, null);
        $expected_post_action = new FrozenFields($transition, 0, []);
        $this->frozen_fields_retriever->shouldReceive('getFrozenFields')->with($transition)->andReturn(
            $expected_post_action
        );

        $result = $this->frozen_fields_factory->loadPostActions($transition);
        $this->assertEquals([$expected_post_action], $result);
    }

    public function testLoadPostActionsReturnsEmptyArray()
    {
        $this->frozen_fields_retriever->shouldReceive('getFrozenFields')->andThrow(new NoFrozenFieldsPostActionException());

        $transition  = new \Transition(null, null, null, null);

        $result = $this->frozen_fields_factory->loadPostActions($transition);
        $this->assertEquals([], $result);
    }

    public function testItImportsActionFromXML()
    {
        $xml_content = <<<XML
            <postaction_frozen_fields>
                <field_id REF="F1"/>
                <field_id REF="F2"/>
            </postaction_frozen_fields>
XML;
        $xml = new SimpleXMLElement($xml_content);

        $int_field   = Mockery::mock(\Tracker_FormElement_Field_Integer::class);
        $float_field = Mockery::mock(\Tracker_FormElement_Field_Float::class);

        $int_field->shouldReceive('getId')->andReturn(0);
        $float_field->shouldReceive('getId')->andReturn(0);

        $mapping = [
            'F1' => $int_field,
            'F2' => $float_field
        ];

        $transition = Mockery::mock(\Transition::class);

        $action = $this->frozen_fields_factory->getInstanceFromXML($xml, $mapping, $transition);

        $this->assertInstanceOf(FrozenFields::class, $action);
        $this->assertCount(2, $action->getFieldIds());
    }

    public function testItSkipsNonExistingFieldsDuringXMLImport()
    {
        $xml_content = <<<XML
            <postaction_frozen_fields>
                <field_id REF="F1"/>
                <field_id REF="F2"/>
            </postaction_frozen_fields>
XML;
        $xml = new SimpleXMLElement($xml_content);

        $int_field = Mockery::mock(\Tracker_FormElement_Field_Integer::class);
        $int_field->shouldReceive('getId')->andReturn(0);

        $mapping = [
            'F1' => $int_field,
        ];

        $transition = Mockery::mock(\Transition::class);

        $action = $this->frozen_fields_factory->getInstanceFromXML($xml, $mapping, $transition);

        $this->assertInstanceOf(FrozenFields::class, $action);
        $this->assertCount(1, $action->getFieldIds());
    }
}
