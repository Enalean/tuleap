<?php
/**
  * Copyright (c) Enalean, 2012 - Present. All rights reserved
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
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */

declare(strict_types=1);

use PHPUnit\Framework\MockObject\MockObject;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_Rule_List_FactoryTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    private Tracker_Rule_List_Dao&MockObject $list_rule_dao;
    private Tracker_Rule_List_Factory $list_rule_factory;

    protected function setUp(): void
    {
        $this->list_rule_dao     = $this->createMock(\Tracker_Rule_List_Dao::class);
        $this->list_rule_factory = new Tracker_Rule_List_Factory($this->list_rule_dao, $this->createMock(Tracker_FormElement_Field_List_BindFactory::class));
    }

    public function testCreateRuleListGeneratesANewObjectThatContainsAllValuesPassed(): void
    {
        $this->list_rule_dao->method('insert')->willReturn(1);

        $source_field_id = 10;
        $target_field_id = 11;
        $tracker_id      = 405;
        $source_value    = 101;
        $target_value    = 102;

        $list_rule = $this->list_rule_factory
            ->create($source_field_id, $target_field_id, $tracker_id, $source_value, $target_value);

        $this->assertInstanceOf(\Tracker_Rule_List::class, $list_rule);
        $this->assertEquals($tracker_id, $list_rule->getTrackerId());
        $this->assertEquals($target_field_id, $list_rule->getTargetFieldId());
        $this->assertEquals($source_field_id, $list_rule->getSourceFieldId());
        $this->assertEquals($source_value, $list_rule->getSourceValue());
        $this->assertEquals($target_value, $list_rule->getTargetValue());
    }

    public function testSearchByIdReturnsNullIfNoEntryIsFoundByTheDao(): void
    {
        $this->list_rule_dao->method('searchById')->willReturn([]);
        $list_rule = $this->list_rule_factory
            ->searchById(999);

        $this->assertNull($list_rule);
    }

    public function testSearchByIdReturnsANewObjectIfOneEntryIsFoundByTheDao(): void
    {
        $data = [
            'source_field_id'   => 46345,
            'target_field_id'   => 465,
            'tracker_id'        => 5458,
            'source_value_id'   => '46345gfv',
            'target_value_id'   => '465',
        ];

        $this->list_rule_dao->method('searchById')->willReturn($data);
        $list_rule = $this->list_rule_factory
            ->searchById(999);

        $this->assertNotNull($list_rule);
    }

    public function testSearchByTrackerIdReturnsNullIfNoEntryIsFoundByTheDao(): void
    {
        $this->list_rule_dao->method('searchByTrackerId')->willReturn([]);
        $list_rule = $this->list_rule_factory
            ->searchByTrackerId(999);

        $this->assertIsArray($list_rule);
        $this->assertCount(0, $list_rule);
    }

    public function testSearchByTrackerIdReturnsAnArrayOfASingleObjectIfOneEntryIsFoundByTheDao(): void
    {
        $data = [
            'source_field_id'   => 46345,
            'target_field_id'   => 465,
            'tracker_id'        => 5458,
            'source_value_id'   => '46345gfv',
            'target_value_id'   => '465',
        ];

        $this->list_rule_dao->method('searchByTrackerId')->willReturn([$data]);
        $list_rules = $this->list_rule_factory
            ->searchByTrackerId(999);

        $this->assertNotNull($list_rules);
        $this->assertIsArray($list_rules);
        $this->assertCount(1, $list_rules);
    }

    public function testDuplicateDoesNotInsertWhenNoRulesExist(): void
    {
        $from_tracker_id = 56;
        $to_tracker_id   = 789;
        $field_mapping   = [
            [
                'from'  => 123,
                'to'    => 888,
            ],
            [
                'from'  => 456,
                'to'    => 999,
            ],
        ];

        $dao = $this->createMock(\Tracker_Rule_List_Dao::class);
        $dao->method('searchByTrackerId')->willReturn([]);
        $dao->expects($this->never())->method('create');

        $factory = new Tracker_Rule_List_Factory($dao, $this->createMock(Tracker_FormElement_Field_List_BindFactory::class));
        $factory->duplicate($from_tracker_id, $to_tracker_id, $field_mapping);
    }

    public function testDuplicateInsertsANewRule(): void
    {
        $from_tracker_id = 56;
        $to_tracker_id   = 789;

        $field_mapping = [
            [
                'from'  => 123,
                'to'    => 888,
                'values' => [
                    789 => 777,
                ],
            ],
            [
                'from'  => 456,
                'to'    => 999,
                'values' => [
                    101 => 666,
                ],
            ],
            [
                'from'  => 1001,
                'to'    => 9999,
                'values' => [
                    1003 => 9998,
                ],
            ],
            [
                'from'  => 1002,
                'to'    => 9997,
                'values' => [
                    1004 => 9996,
                    1005 => 9995,
                ],
            ],
        ];

        $db_data1 = [
            'source_field_id' => 123,
            'target_field_id' => 456,
            'source_value_id' => 789,
            'target_value_id' => 101,
        ];

        $db_data2 = [
            'source_field_id' => 1001,
            'target_field_id' => 1002,
            'source_value_id' => 1003,
            'target_value_id' => 1004,
        ];

        $db_data3 = [
            'source_field_id' => 1001,
            'target_field_id' => 1002,
            'source_value_id' => 1003,
            'target_value_id' => 1005,
        ];

        $dao = $this->createMock(\Tracker_Rule_List_Dao::class);
        $dao->method('searchByTrackerId')->willReturn([$db_data1, $db_data2, $db_data3]);
        $dao->expects($this->exactly(3))->method('create')->willReturnCallback(static fn ($tracker_id, $source_field_id, $source_value_id, $target_field_id, $target_value_id) => match (true) {
            $tracker_id === $to_tracker_id && $source_field_id === 888 && $source_value_id === 777 && $target_field_id === 999 && $target_value_id === 666,
            $tracker_id === $to_tracker_id && $source_field_id === 9999 && $source_value_id === 9998 && $target_field_id === 9997 && $target_value_id === 9996,
            $tracker_id === $to_tracker_id && $source_field_id === 9999 && $source_value_id === 9998 && $target_field_id === 9997 && $target_value_id === 9995 => true
        });

        $factory = new Tracker_Rule_List_Factory($dao, $this->createMock(Tracker_FormElement_Field_List_BindFactory::class));
        $factory->duplicate($from_tracker_id, $to_tracker_id, $field_mapping);
    }

    public function testExport(): void
    {
        $f1 = $this->createMock(\Tuleap\Tracker\FormElement\Field\ListField::class);
        $f1->method('getId')->willReturn(102);
        $f2 = $this->createMock(\Tuleap\Tracker\FormElement\Field\ListField::class);
        $f2->method('getId')->willReturn(103);

        $form_element_factory = $this->createMock(\Tracker_FormElementFactory::class);
        $form_element_factory
            ->method('getFormElementById')
            ->willReturnCallback(
                static fn (int $form_element_id) => match ($form_element_id) {
                    102 => $f1,
                    103 => $f2,
                }
            );

        $bind_f1 = $this->createMock(\Tracker_FormElement_Field_List_Bind_Static::class);
        $bind_f2 = $this->createMock(\Tracker_FormElement_Field_List_Bind_Static::class);

        $f1->method('getBind')->willReturn($bind_f1);
        $f2->method('getBind')->willReturn($bind_f2);

        $bf = $this->createMock(\Tracker_FormElement_Field_List_BindFactory::class);
        $bf->method('getType')->with($bind_f1)->willReturn('static');
        $bf->method('getType')->with($bind_f2)->willReturn('static');

        $root              = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $array_xml_mapping = ['F25' => 102,
            'F28' => 103,
            'values' => [
                'F25-V1' => 801,
                'F25-V2' => 802,
                'F25-V3' => 803,
                'F25-V4' => 804,
                'F28-V1' => 806,
                'F28-V2' => 807,
                'F28-V3' => 808,
                'F28-V4' => 809,
            ],
        ];

        $r1 = new Tracker_Rule_List(1, 101, 103, 806, 102, 803);
        $r2 = new Tracker_Rule_List(1, 101, 103, 806, 102, 804);

        $bind_factory = $this->createMock(Tracker_FormElement_Field_List_BindFactory::class);
        $bind_factory->method('getType')->willReturn(Tracker_FormElement_Field_List_BindFactory::STATIK);
        $trm = $this->getMockBuilder(\Tracker_Rule_List_Factory::class)
            ->setConstructorArgs([
                $this->createMock(Tracker_Rule_List_Dao::class),
                $bind_factory,
            ])
            ->onlyMethods([
                'searchByTrackerId',
            ])
            ->getMock();
        $trm->method('searchByTrackerId')->willReturn([$r1, $r2]);

        $trm->exportToXML($root, $array_xml_mapping, $form_element_factory, 666);
        $this->assertNull($root->dependencies->rule);
    }
}
