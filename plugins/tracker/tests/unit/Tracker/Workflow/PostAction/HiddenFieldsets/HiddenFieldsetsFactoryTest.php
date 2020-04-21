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
use Tracker_FormElementFactory;

class HiddenFieldsetsFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var Mockery\MockInterface */
    private $hidden_fieldsets_dao;

    /** @var HiddenFieldsetsFactory */
    private $hidden_fieldsets_factory;

    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;

    protected function setUp(): void
    {
        $this->hidden_fieldsets_dao = Mockery::mock(HiddenFieldsetsDao::class);
        $this->form_element_factory = Mockery::mock(\Tracker_FormElementFactory::class);

        $this->hidden_fieldsets_factory = new HiddenFieldsetsFactory(
            $this->hidden_fieldsets_dao,
            $this->form_element_factory
        );
    }

    public function testLoadPostActionsReturnsASinglePostAction()
    {
        $this->hidden_fieldsets_dao->shouldReceive('searchByTransitionId')->andReturn(
            [
                ['postaction_id' => 72, 'fieldset_id' => 331],
                ['postaction_id' => 72, 'fieldset_id' => 651],
                ['postaction_id' => 72, 'fieldset_id' => 987]
            ]
        );

        $fieldset_01 = Mockery::mock(\Tracker_FormElement_Container_Fieldset::class);
        $fieldset_02 = Mockery::mock(\Tracker_FormElement_Container_Fieldset::class);
        $fieldset_03 = Mockery::mock(\Tracker_FormElement_Container_Fieldset::class);

        $this->form_element_factory->shouldReceive('getFieldsetById')->with(331)->andReturn($fieldset_01);
        $this->form_element_factory->shouldReceive('getFieldsetById')->with(651)->andReturn($fieldset_02);
        $this->form_element_factory->shouldReceive('getFieldsetById')->with(987)->andReturn($fieldset_03);

        $transition           = Mockery::mock(\Transition::class)->shouldReceive(['getId' => 97])->getMock();
        $expected_post_action = new HiddenFieldsets($transition, 72, [$fieldset_01, $fieldset_02, $fieldset_03]);

        $result = $this->hidden_fieldsets_factory->loadPostActions($transition);
        $this->assertEquals([$expected_post_action], $result);
    }

    public function testLoadPostActionsReturnsEmptyArray()
    {
        $this->hidden_fieldsets_dao->shouldReceive('searchByTransitionId')->andReturn(
            []
        );

        $transition = Mockery::mock(\Transition::class)->shouldReceive(['getId' => 18])->getMock();

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
