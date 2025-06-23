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
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_Rule_Date_FactoryTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    private int $source_field_id = 46345;
    private int $target_field_id = 465;

    private Tracker_Rule_Date_Dao&MockObject $date_rule_dao;

    private Tracker_Rule_Date_Factory $date_rule_factory;

    private Tracker_FormElementFactory&MockObject $element_factory;

    private int $tracker_id = 999;

    private Tracker $tracker;

    private Tracker_FormElement_Field_Date $source_field;
    private Tracker_FormElement_Field_Date $target_field;

    protected function setUp(): void
    {
        $this->tracker = TrackerTestBuilder::aTracker()->withId($this->tracker_id)->build();

        $this->date_rule_dao = $this->createMock(\Tracker_Rule_Date_Dao::class);
        $this->source_field  = DateFieldBuilder::aDateField($this->source_field_id)->build();

        $this->target_field = DateFieldBuilder::aDateField($this->target_field_id)->build();

        $this->element_factory = $this->createMock(\Tracker_FormElementFactory::class);
        $this->element_factory
            ->method('getFormElementById')
            ->willReturnCallback(fn (int $id) => match ($id) {
                $this->source_field_id => $this->source_field,
                $this->target_field_id => $this->target_field,
            });

        $this->date_rule_factory = new Tracker_Rule_Date_Factory($this->date_rule_dao, $this->element_factory);
    }

    public function testCreateRuleDateGeneratesANewObjectThatContainsAllValuesPassed(): void
    {
        $this->date_rule_dao->method('insert')->willReturn(20);

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
        $this->date_rule_dao->method('searchById')->willReturn([]);
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

        $this->date_rule_dao->method('searchById')->with($this->tracker_id, 20)->willReturn($data);
        $this->date_rule_dao->method('searchById')->willReturn([]);
        $date_rule = $this->date_rule_factory
            ->getRule($this->tracker, 20);

        $this->assertNotNull($date_rule);
    }

    public function testSearchByTrackerIdReturnsNullIfNoEntryIsFoundByTheDao(): void
    {
        $this->date_rule_dao->method('searchByTrackerId')->willReturn([]);
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

        $this->date_rule_dao->method('searchByTrackerId')->willReturn([$data]);
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
        $this->date_rule_dao->expects($this->once())->method('deleteById')->with($this->tracker_id, $rule_id);
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

        $dao = $this->createMock(\Tracker_Rule_Date_Dao::class);
        $dao->method('searchByTrackerId')->willReturn([]);
        $dao->expects($this->never())->method('insert');
        $form_factory = $this->createMock(\Tracker_FormElementFactory::class);

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

        $dao = $this->createMock(\Tracker_Rule_Date_Dao::class);
        $dao->method('searchByTrackerId')->willReturn([$db_data]);
        $dao->expects($this->once())->method('insert')->with($to_tracker_id, 888, 999, Tracker_Rule_Date::COMPARATOR_LESS_THAN);
        $form_factory = $this->createMock(\Tracker_FormElementFactory::class);

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

        $dao = $this->createMock(\Tracker_Rule_Date_Dao::class);
        $dao->expects($this->atLeastOnce())->method('searchByTrackerId')->willReturn([$db_data1, $db_data2]);
        $matcher = $this->atLeastOnce();
        $dao->expects($matcher)->method('insert')->willReturnCallback(static fn (int $tracker_id, int $source_field_id, int $target_field_id, string $comparator) => match (true) {
            $matcher->numberOfInvocations() === 1 && $tracker_id === $to_tracker_id && $source_field_id === 555 && $target_field_id === 666 && $comparator === Tracker_Rule_Date::COMPARATOR_LESS_THAN,
            $matcher->numberOfInvocations() === 2 && $tracker_id === $to_tracker_id && $source_field_id === 777 && $target_field_id === 888 && $comparator === Tracker_Rule_Date::COMPARATOR_LESS_THAN => true
        });
        $form_factory = $this->createMock(\Tracker_FormElementFactory::class);

        $factory = new Tracker_Rule_Date_Factory($dao, $form_factory);
        $factory->duplicate($from_tracker_id, $to_tracker_id, $field_mapping);
    }

    public function testItDelegatesUsedDateFieldsRetrievalToElementFactory(): void
    {
        $tracker          = TrackerTestBuilder::aTracker()->build();
        $used_date_fields = ['of', 'fields'];
        $this->element_factory->expects($this->once())->method('getUsedDateFields')->with($tracker)->willReturn($used_date_fields);
        $this->assertEquals($used_date_fields, $this->date_rule_factory->getUsedDateFields($tracker));
    }

    public function testItDelegatesUsedDateFieldByIdRetrievalToElementFactory(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->build();
        $this->element_factory->expects($this->once())->method('getUsedDateFieldById')->with($tracker, $this->source_field_id)->willReturn($this->source_field);
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

        $trm = $this->createPartialMock(\Tracker_Rule_Date_Factory::class, ['searchByTrackerId']);
        $trm->method('searchByTrackerId')->willReturn([$r1, $r2]);

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

        $this->date_rule_dao->expects($this->once())->method('save')->with($id, $this->source_field_id, $this->target_field_id, '>')->willReturn(true);
        $success = $this->date_rule_factory->save($rule);
        $this->assertTrue($success);
    }
}
