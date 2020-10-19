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
use SimpleXMLElement;

class HiddenFieldsetsFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var Mockery\MockInterface */
    private $hidden_fieldsets_dao;

    /** @var HiddenFieldsetsFactory */
    private $hidden_fieldsets_factory;

    /**
     * @var HiddenFieldsetsRetriever
     */
    private $hidden_fieldsets_retriever;

    protected function setUp(): void
    {
        $this->hidden_fieldsets_dao       = Mockery::mock(HiddenFieldsetsDao::class);
        $this->hidden_fieldsets_retriever = Mockery::mock(HiddenFieldsetsRetriever::class);

        $this->hidden_fieldsets_factory = new HiddenFieldsetsFactory(
            $this->hidden_fieldsets_dao,
            $this->hidden_fieldsets_retriever
        );
    }

    public function testLoadPostActionsReturnsASinglePostAction()
    {
        $transition = new \Transition(null, null, null, null);

        $expected_post_action = new HiddenFieldsets($transition, 0, []);

        $this->hidden_fieldsets_retriever
            ->shouldReceive('getHiddenFieldsets')
            ->with($transition)
            ->andReturn($expected_post_action);

        $result = $this->hidden_fieldsets_factory->loadPostActions($transition);
        $this->assertEquals([$expected_post_action], $result);
    }

    public function testLoadPostActionsReturnsEmptyArray()
    {
        $transition = new \Transition(null, null, null, null);
        $this->hidden_fieldsets_retriever
            ->shouldReceive('getHiddenFieldsets')
            ->with($transition)
            ->andThrow(new NoHiddenFieldsetsPostActionException());

        $result = $this->hidden_fieldsets_factory->loadPostActions($transition);
        $this->assertEquals([], $result);
    }

    public function testItImportsActionFromXML()
    {
        $xml_content = <<<XML
            <postaction_hidden_fieldsets>
                <fieldset_id REF="F1"/>
                <fieldset_id REF="F2"/>
            </postaction_hidden_fieldsets>
XML;
        $xml = new SimpleXMLElement($xml_content);

        $fieldset_01 = Mockery::mock(\Tracker_FormElement_Container_Fieldset::class);
        $fieldset_02 = Mockery::mock(\Tracker_FormElement_Container_Fieldset::class);

        $fieldset_01->shouldReceive('getID')->andReturn(0);
        $fieldset_02->shouldReceive('getID')->andReturn(0);

        $mapping = [
            'F1' => $fieldset_01,
            'F2' => $fieldset_02
        ];

        $transition = Mockery::mock(\Transition::class);

        $action = $this->hidden_fieldsets_factory->getInstanceFromXML($xml, $mapping, $transition);

        $this->assertInstanceOf(HiddenFieldsets::class, $action);
        $this->assertCount(2, $action->getFieldsets());
    }

    public function testItSkipsNonExistingFieldsDuringXMLImport()
    {
        $xml_content = <<<XML
            <postaction_hidden_fieldsets>
                <fieldset_id REF="F1"/>
                <fieldset_id REF="F2"/>
            </postaction_hidden_fieldsets>
XML;
        $xml = new SimpleXMLElement($xml_content);

        $fieldset_01 = Mockery::mock(\Tracker_FormElement_Container_Fieldset::class);
        $fieldset_01->shouldReceive('getID')->andReturn(0);

        $mapping = [
            'F1' => $fieldset_01,
        ];

        $transition = Mockery::mock(\Transition::class);

        $action = $this->hidden_fieldsets_factory->getInstanceFromXML($xml, $mapping, $transition);

        $this->assertInstanceOf(HiddenFieldsets::class, $action);
        $this->assertCount(1, $action->getFieldsets());
    }
}
