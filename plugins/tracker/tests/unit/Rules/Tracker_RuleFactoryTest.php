<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

namespace Tuleap\Tracker\Rule;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use SimpleXMLElement;
use Tracker;
use Tracker_FormElement_Field_List;
use Tracker_Rule_Date;
use Tracker_Rule_Date_Dao;
use Tracker_Rule_Date_Factory;
use Tracker_Rule_List;
use Tracker_Rule_List_Dao;
use Tracker_Rule_List_Factory;
use Tracker_RuleDao;
use Tracker_RuleFactory;

//phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_RuleFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var string
     */
    private $xmlstr;
    /**
     * @var SimpleXMLElement
     */
    private $xml;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker
     */
    private $tracker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_FormElement_Field_List
     */
    private $f1;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_FormElement_Field_List
     */
    private $f2;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_FormElement_Field_List
     */
    private $f3;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_FormElement_Field_List
     */
    private $f4;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_FormElement_Field_List
     */
    private $f5;

    protected function setUp(): void
    {
        $this->xmlstr  = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
    <rules>
    </rules>
XML;
        $this->xml     = new SimpleXMLElement($this->xmlstr);
        $this->tracker = Mockery::mock(Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturn(888);
        $this->f1 = Mockery::mock(Tracker_FormElement_Field_List::class);
        $this->f1->shouldReceive('getId')->andReturn(102);
        $this->f2 = Mockery::mock(Tracker_FormElement_Field_List::class);
        $this->f2->shouldReceive('getId')->andReturn(103);
        $this->f3 = Mockery::mock(Tracker_FormElement_Field_List::class);
        $this->f3->shouldReceive('getId')->andReturn(104);
        $this->f4 = Mockery::mock(Tracker_FormElement_Field_List::class);
        $this->f4->shouldReceive('getId')->andReturn(105);
        $this->f5 = Mockery::mock(Tracker_FormElement_Field_List::class);
        $this->f5->shouldReceive('getId')->andReturn(106);
    }

    public function testImportListRules()
    {
        $list_rules = $this->xml->addChild('list_rules');

        $rule1 = $list_rules->addChild('rule');
        $rule1->addChild('source_field')->addAttribute('REF', 'F28');
        $rule1->addChild('target_field')->addAttribute('REF', 'F25');
        $rule1->addChild('source_value')->addAttribute('REF', 'F28-V1');
        $rule1->addChild('target_value')->addAttribute('REF', 'F25-V3');

        $rule2 = $list_rules->addChild('rule');
        $rule2->addChild('source_field')->addAttribute('REF', 'F28');
        $rule2->addChild('target_field')->addAttribute('REF', 'F25');
        $rule2->addChild('source_value')->addAttribute('REF', 'F28-V1');
        $rule2->addChild('target_value')->addAttribute('REF', 'F25-V4');


        $array_xml_mapping = [
            'F25' => $this->f1,
            'F28' => $this->f2,
            'F25-V3' => $this->f4,
            'F25-V4' => $this->f5,
            'F28-V1' => $this->f3,
        ];

        $tracker_rule_dao = Mockery::mock(Tracker_RuleDao::class);
        $rule_factory     = new Tracker_RuleFactory($tracker_rule_dao);
        $rules            = $rule_factory->getInstanceFromXML($this->xml, $array_xml_mapping, $this->tracker);

        $list_rule_expected = new Tracker_Rule_List();
        $list_rule_expected->setSourceValue($array_xml_mapping['F28-V1'])
            ->setTargetValue($array_xml_mapping['F25-V3'])
            ->setId(0)
            ->setTrackerId($this->tracker->getId())
            ->setSourceField($array_xml_mapping['F28'])
            ->setTargetField($array_xml_mapping['F25']);

        $list_rule_expected2 = new Tracker_Rule_List();
        $list_rule_expected2->setSourceValue($array_xml_mapping['F28-V1'])
            ->setTargetValue($array_xml_mapping['F25-V4'])
            ->setId(0)
            ->setTrackerId($this->tracker->getId())
            ->setSourceField($array_xml_mapping['F28'])
            ->setTargetField($array_xml_mapping['F25']);

        $this->assertEquals(2, count($rules['list_rules']));
        $this->assertEquals($list_rule_expected, $rules['list_rules'][0]);
        $this->assertEquals($list_rule_expected2, $rules['list_rules'][1]);
    }

    public function testImportDateRules()
    {
        $date_rules = $this->xml->addChild('date_rules');

        $rule1 = $date_rules->addChild('rule');
        $rule1->addChild('source_field')->addAttribute('REF', 'F28');
        $rule1->addChild('target_field')->addAttribute('REF', 'F25');
        $rule1->addChild('comparator')->addAttribute('type', Tracker_Rule_Date::COMPARATOR_NOT_EQUALS);

        $rule2 = $date_rules->addChild('rule');
        $rule2->addChild('source_field')->addAttribute('REF', 'F29');
        $rule2->addChild('target_field')->addAttribute('REF', 'F30');
        $rule2->addChild('comparator')->addAttribute('type', Tracker_Rule_Date::COMPARATOR_LESS_THAN_OR_EQUALS);

        $array_xml_mapping = [
            'F25' => $this->f3,
            'F28' => $this->f1,
            'F29' => $this->f2,
            'F30' => $this->f4,
        ];

        $tracker_rule_dao = Mockery::mock(Tracker_RuleDao::class);
        $rule_factory     = new Tracker_RuleFactory($tracker_rule_dao);
        $rules            = $rule_factory->getInstanceFromXML($this->xml, $array_xml_mapping, $this->tracker);

        $date_rule_expected = new Tracker_Rule_Date();
        $date_rule_expected->setComparator(Tracker_Rule_Date::COMPARATOR_NOT_EQUALS)
            ->setTrackerId($this->tracker->getId())
            ->setSourceField($array_xml_mapping['F28'])
            ->setTargetField($array_xml_mapping['F25']);

        $date_rule_expected2 = new Tracker_Rule_Date();
        $date_rule_expected2->setComparator(Tracker_Rule_Date::COMPARATOR_LESS_THAN_OR_EQUALS)
            ->setTrackerId($this->tracker->getId())
            ->setSourceField($array_xml_mapping['F29'])
            ->setTargetField($array_xml_mapping['F30']);

        $this->assertEquals(2, count($rules['date_rules']));
        $this->assertEquals($date_rule_expected, $rules['date_rules'][0]);
        $this->assertEquals($date_rule_expected2, $rules['date_rules'][1]);
    }

    public function testDuplicateCallsListAndDateDuplicate()
    {
        $from_tracker_id = 10;
        $to_tracker_id   = 53;
        $field_mapping   = [];

        $rule_list_factory = Mockery::mock(Tracker_Rule_List_Factory::class);
        $rule_date_factory = Mockery::mock(Tracker_Rule_Date_Factory::class);
        $rule_factory      = Mockery::mock(Tracker_RuleFactory::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $rule_factory->shouldReceive('getListFactory')->andReturn($rule_list_factory);
        $rule_factory->shouldReceive('getDateFactory')->andReturn($rule_date_factory);

        $rule_list_factory->shouldReceive('duplicate')->withArgs(
            [$from_tracker_id, $to_tracker_id, $field_mapping]
        )->once();
        $rule_date_factory->shouldReceive('duplicate')->withArgs(
            [$from_tracker_id, $to_tracker_id, $field_mapping]
        )->once();

        $rule_factory->duplicate($from_tracker_id, $to_tracker_id, $field_mapping);
    }

    public function testGetListOrDateFactoriesReturnNewInstancesWhenNotSet()
    {
        $list_dao = Mockery::mock(Tracker_Rule_List_Dao::class);
        $date_dao = Mockery::mock(Tracker_Rule_Date_Dao::class);
        $rule_dao = Mockery::mock(Tracker_RuleDao::class);

        $factory = new Tracker_RuleFactory($rule_dao);
        $factory->setListDao($list_dao);
        $factory->setDateDao($date_dao);

        $this->assertInstanceOf(Tracker_Rule_List_Factory::class, $factory->getListFactory());
        $this->assertInstanceOf(Tracker_Rule_Date_Factory::class, $factory->getDateFactory());
    }

    public function testSaveObjectCallsDateAndListFactoryInsertMethods()
    {
        $rule_dao = Mockery::mock(Tracker_RuleDao::class);

        $list = Mockery::mock(Tracker_Rule_List::class);
        $list->shouldReceive('setTrackerId')->withArgs([888])->once();

        $date = Mockery::mock(Tracker_Rule_Date::class);
        $date->shouldReceive('setTrackerId')->withArgs([888])->once();

        $list_rules = [
            $list,
        ];
        $date_rules = [
            $date,
        ];

        $rules = [
            'list_rules' => $list_rules,
            'date_rules' => $date_rules,
        ];

        $date_factory = Mockery::mock(Tracker_Rule_Date_Factory::class);
        $date_factory->shouldReceive('insert')->withArgs([$date])->once();

        $list_factory = Mockery::mock(Tracker_Rule_List_Factory::class);
        $list_factory->shouldReceive('insert')->withArgs([$list])->once();

        $factory = new Tracker_RuleFactory($rule_dao);
        $factory->setListFactory($list_factory);
        $factory->setDateFactory($date_factory);

        $factory->saveObject($rules, $this->tracker);
    }

    public function testSaveObjectCallsDateInsertMethodWhenNoListRulesAreInArray()
    {
        $rule_dao = Mockery::mock(Tracker_RuleDao::class);
        $date     = Mockery::mock(Tracker_Rule_Date::class);
        $date->shouldReceive('setTrackerId')->withArgs([888])->once();

        $date_rules = [
            $date,
        ];
        $rules      = [
            'date_rules' => $date_rules,
        ];

        $date_factory = Mockery::mock(Tracker_Rule_Date_Factory::class);
        $date_factory->shouldReceive('insert')->with($date)->once();

        $factory = new Tracker_RuleFactory($rule_dao);
        $factory->setDateFactory($date_factory);

        $factory->saveObject($rules, $this->tracker);
    }

    public function testSaveObjectCallsListInsertMethodWhenNoDateRulesAreInArray()
    {
        $rule_dao = Mockery::mock(Tracker_RuleDao::class);
        $list     = Mockery::mock(Tracker_Rule_List::class);
        $list->shouldReceive('setTrackerId')->withArgs([888])->once();

        $list_rules = [
            $list,
        ];

        $rules = [
            'list_rules' => $list_rules,
        ];

        $list_factory = Mockery::mock(Tracker_Rule_List_Factory::class);
        $list_factory->shouldReceive('insert')->with($list)->once();

        $factory = new Tracker_RuleFactory($rule_dao);
        $factory->setListFactory($list_factory);

        $factory->saveObject($rules, $this->tracker);
    }
}
