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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_Rule_List_FactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Tracker_Rule_List_Dao
     */
    private $list_rule_dao;

    /**
     *
     * @var Tracker_Rule_List_Factory
     */
    private $list_rule_factory;

    protected function setUp(): void
    {
        $this->list_rule_dao     = \Mockery::spy(\Tracker_Rule_List_Dao::class);
        $this->list_rule_factory = new Tracker_Rule_List_Factory($this->list_rule_dao);
    }

    public function testCreateRuleListGeneratesANewObjectThatContainsAllValuesPassed(): void
    {
        $this->list_rule_dao->shouldReceive('insert')->andReturns(true);

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
        $this->list_rule_dao->shouldReceive('searchById')->andReturns([]);
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

        $this->list_rule_dao->shouldReceive('searchById')->andReturns($data);
        $list_rule = $this->list_rule_factory
            ->searchById(999);

        $this->assertNotNull($list_rule);
    }

    public function testSearchByTrackerIdReturnsNullIfNoEntryIsFoundByTheDao(): void
    {
        $this->list_rule_dao->shouldReceive('searchByTrackerId')->andReturns([]);
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

        $this->list_rule_dao->shouldReceive('searchByTrackerId')->andReturns([$data]);
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

        $dao = \Mockery::spy(\Tracker_Rule_List_Dao::class);
        $dao->shouldReceive('searchByTrackerId')->andReturns([]);
        $dao->shouldReceive('create')->never();

        $factory = new Tracker_Rule_List_Factory($dao);
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

        $dao = \Mockery::spy(\Tracker_Rule_List_Dao::class);
        $dao->shouldReceive('searchByTrackerId')->andReturns([$db_data1, $db_data2, $db_data3]);
        $dao->shouldReceive('create')->with($to_tracker_id, 888, 777, 999, 666)->once();
        $dao->shouldReceive('create')->with($to_tracker_id, 9999, 9998, 9997, 9996)->once();
        $dao->shouldReceive('create')->with($to_tracker_id, 9999, 9998, 9997, 9995)->once();

        $factory = new Tracker_Rule_List_Factory($dao);
        $factory->duplicate($from_tracker_id, $to_tracker_id, $field_mapping);
    }

    public function testExport(): void
    {
        $f1 = \Mockery::spy(\Tracker_FormElement_Field_List::class)->shouldReceive('getId')->andReturns(102)->getMock();
        $f2 = \Mockery::spy(\Tracker_FormElement_Field_List::class)->shouldReceive('getId')->andReturns(103)->getMock();

        $form_element_factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        $form_element_factory->shouldReceive('getFormElementById')->with(102)->andReturns($f1);
        $form_element_factory->shouldReceive('getFormElementById')->with(103)->andReturns($f2);

        $bind_f1 = \Mockery::spy(\Tracker_FormElement_Field_List_Bind_Static::class);
        $bind_f2 = \Mockery::spy(\Tracker_FormElement_Field_List_Bind_Static::class);

        $f1->shouldReceive('getBind')->andReturns($bind_f1);
        $f2->shouldReceive('getBind')->andReturns($bind_f2);

        $bf = \Mockery::spy(\Tracker_FormElement_Field_List_BindFactory::class);
        $bf->shouldReceive('getType')->with($bind_f1)->andReturns('static');
        $bf->shouldReceive('getType')->with($bind_f2)->andReturns('static');

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

        $trm = \Mockery::mock(\Tracker_Rule_List_Factory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $trm->shouldReceive('searchByTrackerId')->andReturns([$r1, $r2]);

        $trm->exportToXML($root, $array_xml_mapping, $form_element_factory, 666);
        $this->assertNull($root->dependencies->rule);
    }
}
