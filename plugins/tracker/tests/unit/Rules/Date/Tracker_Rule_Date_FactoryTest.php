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
final class Tracker_Rule_Date_FactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $source_field_id = 46345;
    private $target_field_id = 465;

    /** @var Tracker_Rule_Date_Dao */
    private $date_rule_dao;

    /** @var Tracker_Rule_Date_Factory */
    private $date_rule_factory;

    /** @var Tracker_FormElementFactory */
    private $element_factory;

    /** @var int */
    private $tracker_id = 999;

    /** @var Tracker */
    private $tracker;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_Date
     */
    private $source_field;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_Date
     */
    private $target_field;

    protected function setUp(): void
    {
        $this->tracker = \Mockery::spy(\Tracker::class)->shouldReceive('getId')->andReturns($this->tracker_id)->getMock();

        $this->date_rule_dao = \Mockery::spy(\Tracker_Rule_Date_Dao::class);
        $this->source_field  = \Mockery::spy(\Tracker_FormElement_Field_Date::class);
        $this->source_field->shouldReceive('getId')->andReturns($this->source_field_id);

        $this->target_field = \Mockery::spy(\Tracker_FormElement_Field_Date::class);
        $this->target_field->shouldReceive('getId')->andReturns(465);

        $this->element_factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        $this->element_factory->shouldReceive('getFormElementById')->with($this->source_field_id)->andReturns($this->source_field);
        $this->element_factory->shouldReceive('getFormElementById')->with(465)->andReturns($this->target_field);

        $this->date_rule_factory = new Tracker_Rule_Date_Factory($this->date_rule_dao, $this->element_factory);
    }

    public function testCreateRuleDateGeneratesANewObjectThatContainsAllValuesPassed(): void
    {
        $this->date_rule_dao->shouldReceive('insert')->andReturns(20);

        $comparator = Tracker_Rule_Date::COMPARATOR_GREATER_THAN;

        $date_rule = $this->date_rule_factory->create(
            $this->source_field_id,
            $this->target_field_id,
            $this->tracker_id,
            $comparator
        );

        $this->assertInstanceOf(\Tracker_Rule_Date::class, $date_rule);
        $this->assertEquals($this->tracker_id, $date_rule->getTrackerId());
        $this->assertEquals($this->target_field_id, $date_rule->getTargetFieldId());
        $this->assertEquals($this->source_field_id, $date_rule->getSourceFieldId());
        $this->assertEquals($comparator, $date_rule->getComparator());
        $this->assertEquals(20, $date_rule->getId());
    }

    public function testSearchByIdReturnsNullIfNoEntryIsFoundByTheDao(): void
    {
        $this->date_rule_dao->shouldReceive('searchById')->andReturns([]);
        $date_rule = $this->date_rule_factory
            ->getRule($this->tracker, 20);

        $this->assertNull($date_rule);
    }

    public function testSearchByIdReturnsANewObjectIfOneEntryIsFoundByTheDao(): void
    {
        $data = [
            'id'                => 20,
            'comparator'        => Tracker_Rule_Date::COMPARATOR_LESS_THAN_OR_EQUALS,
            'source_field_id'   => $this->source_field_id,
            'target_field_id'   => $this->target_field_id,
            'tracker_id'        => $this->tracker_id,
        ];

        $this->date_rule_dao->shouldReceive('searchById')->with($this->tracker_id, 20)->andReturns($data);
        $this->date_rule_dao->shouldReceive('searchById')->andReturns([]);
        $date_rule = $this->date_rule_factory
            ->getRule($this->tracker, 20);

        $this->assertNotNull($date_rule);
    }

    public function testSearchByTrackerIdReturnsNullIfNoEntryIsFoundByTheDao(): void
    {
        $this->date_rule_dao->shouldReceive('searchByTrackerId')->andReturns([]);
        $date_rule = $this->date_rule_factory
            ->searchByTrackerId($this->tracker_id);

        $this->assertIsArray($date_rule);
        $this->assertCount(0, $date_rule);
    }

    public function testSearchByTrackerIdReturnsAnArrayOfASingleObjectIfOneEntryIsFoundByTheDao(): void
    {
        $data = [
            'comparator'        => Tracker_Rule_Date::COMPARATOR_LESS_THAN_OR_EQUALS,
            'source_field_id'   => $this->source_field_id,
            'target_field_id'   => $this->target_field_id,
            'tracker_id'        => $this->tracker_id,
            'id'                => 20,
        ];

        $this->date_rule_dao->shouldReceive('searchByTrackerId')->andReturns([$data]);
        $date_rules = $this->date_rule_factory
            ->searchByTrackerId($this->tracker_id);

        $this->assertNotNull($date_rules);
        $this->assertIsArray($date_rules);
        $this->assertCount(1, $date_rules);

        $rule                  = $date_rules[0];
        $obtained_source_field = $rule->getSourceField();
        $obtained_target_field = $rule->getTargetField();

        $this->assertEquals($this->source_field, $obtained_source_field);
        $this->assertEquals($this->target_field, $obtained_target_field);
        $this->assertEquals(20, $rule->getId());
    }

    public function testItDelegatesDeletionToDao(): void
    {
        $rule_id = '456';
        $this->date_rule_dao->shouldReceive('deleteById')->with($this->tracker_id, $rule_id)->once();
        $this->date_rule_factory->deleteById($this->tracker_id, $rule_id);
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

        $dao = \Mockery::spy(\Tracker_Rule_Date_Dao::class);
        $dao->shouldReceive('searchByTrackerId')->andReturns([]);
        $dao->shouldReceive('insert')->never();
        $form_factory = \Mockery::spy(\Tracker_FormElementFactory::class);

        $factory = new Tracker_Rule_Date_Factory($dao, $form_factory);
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
            ],
            [
                'from'  => 456,
                'to'    => 999,
            ],
        ];

        $db_data = [
            'source_field_id' => 123,
            'target_field_id' => 456,
            'comparator'      => Tracker_Rule_Date::COMPARATOR_LESS_THAN,
        ];

        $dao = \Mockery::spy(\Tracker_Rule_Date_Dao::class);
        $dao->shouldReceive('searchByTrackerId')->andReturns([$db_data]);
        $dao->shouldReceive('insert')->with($to_tracker_id, 888, 999, Tracker_Rule_Date::COMPARATOR_LESS_THAN)->once();
        $form_factory = \Mockery::spy(\Tracker_FormElementFactory::class);

        $factory = new Tracker_Rule_Date_Factory($dao, $form_factory);
        $factory->duplicate($from_tracker_id, $to_tracker_id, $field_mapping);
    }

    public function testDuplicateInsertsMultipleRules(): void
    {
        $from_tracker_id = 56;
        $to_tracker_id   = 789;

        $field_mapping = [
            [
                'from'  => 111,
                'to'    => 555,
            ],
            [
                'from'  => 222,
                'to'    => 666,
            ],
            [
                'from'  => 333,
                'to'    => 777,
            ],
            [
                'from'  => 444,
                'to'    => 888,
            ],
        ];

        $db_data1 = [
            'source_field_id' => 111,
            'target_field_id' => 222,
            'comparator'      => Tracker_Rule_Date::COMPARATOR_LESS_THAN,
        ];
        $db_data2 = [
            'source_field_id' => 333,
            'target_field_id' => 444,
            'comparator'      => Tracker_Rule_Date::COMPARATOR_LESS_THAN,
        ];

        $dao = \Mockery::spy(\Tracker_Rule_Date_Dao::class);
        $dao->shouldReceive('searchByTrackerId')->andReturns([$db_data1, $db_data2])->atLeast()->once();
        $dao->shouldReceive('insert')->with($to_tracker_id, 555, 666, Tracker_Rule_Date::COMPARATOR_LESS_THAN)->ordered()->atLeast()->once();
        $dao->shouldReceive('insert')->with($to_tracker_id, 777, 888, Tracker_Rule_Date::COMPARATOR_LESS_THAN)->ordered()->atLeast()->once();
        $form_factory = \Mockery::spy(\Tracker_FormElementFactory::class);

        $factory = new Tracker_Rule_Date_Factory($dao, $form_factory);
        $factory->duplicate($from_tracker_id, $to_tracker_id, $field_mapping);
    }

    public function testItDelegatesUsedDateFieldsRetrievalToElementFactory(): void
    {
        $tracker          = \Mockery::spy(\Tracker::class);
        $used_date_fields = ['of', 'fields'];
        $this->element_factory->shouldReceive('getUsedDateFields')->with($tracker)->once()->andReturns($used_date_fields);
        $this->assertEquals($used_date_fields, $this->date_rule_factory->getUsedDateFields($tracker));
    }

    public function testItDelegatesUsedDateFieldByIdRetrievalToElementFactory(): void
    {
        $tracker = \Mockery::spy(\Tracker::class);
        $this->element_factory->shouldReceive('getUsedDateFieldById')->with($tracker, $this->source_field_id)->once()->andReturns($this->source_field);
        $this->assertEquals($this->source_field, $this->date_rule_factory->getUsedDateFieldById($tracker, $this->source_field_id));
    }

    public function testExport(): void
    {
        $root              = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $array_xml_mapping = ['F25' => 102,
            'F28' => 103,
            'F29' => 801,
            'F22' => 806,
        ];

        $r1 = new Tracker_Rule_Date();
        $r1->setComparator(Tracker_Rule_Date::COMPARATOR_NOT_EQUALS)
            ->setSourceFieldId(102)
            ->setTargetFieldId(801);

        $r2 = new Tracker_Rule_Date();
        $r2->setComparator(Tracker_Rule_Date::COMPARATOR_GREATER_THAN_OR_EQUALS)
            ->setSourceFieldId(103)
            ->setTargetFieldId(806);

        $trm = \Mockery::mock(\Tracker_Rule_Date_Factory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $trm->shouldReceive('searchByTrackerId')->andReturns([$r1, $r2]);

        $trm->exportToXML($root, $array_xml_mapping, 666);
        $this->assertNull($root->dependencies->rule);
    }

    public function testItDelegatesSaveToDao(): void
    {
        $id   = 20;
        $rule = new Tracker_Rule_Date();
        $rule->setId($id);
        $rule->setSourceField($this->source_field);
        $rule->setComparator('>');
        $rule->setTargetField($this->target_field);

        $this->date_rule_dao->shouldReceive('save')->with($id, $this->source_field_id, $this->target_field_id, '>')->once()->andReturns(true);
        $success = $this->date_rule_factory->save($rule);
        $this->assertTrue($success);
    }
}
