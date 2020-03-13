<?php
/**
  * Copyright (c) Enalean, 2012 - 2018. All rights reserved
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
require_once __DIR__ . '/../../../bootstrap.php';

class Tracker_Rule_List_FactoryTest extends TuleapTestCase
{

    /**
     * @var Tracker_Rule_List_Dao
     */
    protected $list_rule_dao;

    /**
     *
     * @var Tracker_Rule_List_Factory
     */
    protected $list_rule_factory;

    /** @var XML_Security */
    protected $xml_security;

    public function setUp()
    {
        parent::setUp();

        $this->list_rule_dao = mock('Tracker_Rule_List_Dao');
        $this->list_rule_factory = new Tracker_Rule_List_Factory($this->list_rule_dao);

        $this->xml_security = new XML_Security();
        $this->xml_security->enableExternalLoadOfEntities();
    }

    public function tearDown()
    {
        $this->xml_security->disableExternalLoadOfEntities();

        parent::tearDown();
    }

    public function testCreateRuleListGeneratesANewObjectThatContainsAllValuesPassed()
    {
        stub($this->list_rule_dao)->insert()->returns(true);

        $source_field_id = 10;
        $target_field_id = 11;
        $tracker_id      = 405;
        $source_value    = 101;
        $target_value    = 102;

        $list_rule = $this->list_rule_factory
                ->create($source_field_id, $target_field_id, $tracker_id, $source_value, $target_value);

        $this->assertIsA($list_rule, 'Tracker_Rule_List');
        $this->assertEqual($list_rule->getTrackerId(), $tracker_id);
        $this->assertEqual($list_rule->getTargetFieldId(), $target_field_id);
        $this->assertEqual($list_rule->getSourceFieldId(), $source_field_id);
        $this->assertEqual($list_rule->getSourceValue(), $source_value);
        $this->assertEqual($list_rule->getTargetValue(), $target_value);
    }

    public function testSearchByIdReturnsNullIfNoEntryIsFoundByTheDao()
    {
        stub($this->list_rule_dao)->searchById()->returns(false);
        $list_rule = $this->list_rule_factory
                ->searchById(999);

        $this->assertNull($list_rule);
    }

    public function testSearchByIdReturnsANewObjectIfOneEntryIsFoundByTheDao()
    {
        $data = array(
            'source_field_id'   => 46345,
            'target_field_id'   => 465,
            'tracker_id'        => 5458,
            'source_value_id'   => '46345gfv',
            'target_value_id'   => '465',
        );

        stub($this->list_rule_dao)->searchById()->returns($data);
        $list_rule = $this->list_rule_factory
                ->searchById(999);

        $this->assertNotNull($list_rule);
    }

    public function testSearchByTrackerIdReturnsNullIfNoEntryIsFoundByTheDao()
    {
        stub($this->list_rule_dao)->searchByTrackerId()->returnsEmptyDar();
        $list_rule = $this->list_rule_factory
                ->searchByTrackerId(999);

        $this->assertTrue(is_array($list_rule));
        $this->assertCount($list_rule, 0);
    }

    public function testSearchByTrackerIdReturnsAnArrayOfASingleObjectIfOneEntryIsFoundByTheDao()
    {
        $data_access_result = mock('DataAccessResult');

        $data = array(
            'source_field_id'   => 46345,
            'target_field_id'   => 465,
            'tracker_id'        => 5458,
            'source_value_id'   => '46345gfv',
            'target_value_id'   => '465',
        );

        stub($data_access_result)->rowCount()->returns(1);
        stub($data_access_result)->getRow()->at(1)->returns($data);
        stub($data_access_result)->getRow()->at(2)->returns(false);

        stub($this->list_rule_dao)->searchByTrackerId()->returnsDar($data);
        $list_rules = $this->list_rule_factory
                ->searchByTrackerId(999);

        $this->assertNotNull($list_rules);
        $this->assertIsA($list_rules, 'array');
        $this->assertCount($list_rules, 1);
    }

    public function testDuplicateDoesNotInsertWhenNoRulesExist()
    {
        $from_tracker_id = 56;
        $to_tracker_id   = 789;
        $field_mapping   = array(
            array(
                'from'  => 123,
                'to'    => 888
            ),
            array(
                'from'  => 456,
                'to'    => 999
            ),
        );

        $db_data = false;

        $dao = mock('Tracker_Rule_List_Dao');
        stub($dao)->searchByTrackerId()->returnsDar($db_data);
        stub($dao)->create()->never();
        $form_factory = mock('Tracker_FormElementFactory');

        $factory = new Tracker_Rule_List_Factory($dao, $form_factory);
        $factory->duplicate($from_tracker_id, $to_tracker_id, $field_mapping);
    }

    public function testDuplicateInsertsANewRule()
    {
        $from_tracker_id = 56;
        $to_tracker_id   = 789;

        $field_mapping   = array(
            array(
                'from'  => 123,
                'to'    => 888,
                'values' => array(
                    789 => 777
                )
            ),
            array(
                'from'  => 456,
                'to'    => 999,
                'values' => array(
                    101 => 666
                )
            ),
            array(
                'from'  => 1001,
                'to'    => 9999,
                'values' => array(
                    1003 => 9998
                )
            ),
            array(
                'from'  => 1002,
                'to'    => 9997,
                'values' => array(
                    1004 => 9996,
                    1005 => 9995
                )
            ),
        );

        $db_data1 = array(
            'source_field_id' => 123,
            'target_field_id' => 456,
            'source_value_id' => 789,
            'target_value_id' => 101
        );

        $db_data2 = array(
            'source_field_id' => 1001,
            'target_field_id' => 1002,
            'source_value_id' => 1003,
            'target_value_id' => 1004
        );

        $db_data3 = array(
            'source_field_id' => 1001,
            'target_field_id' => 1002,
            'source_value_id' => 1003,
            'target_value_id' => 1005
        );

        $dao = mock('Tracker_Rule_List_Dao');
        stub($dao)->searchByTrackerId()->returnsDar($db_data1, $db_data2, $db_data3);
        stub($dao)->create($to_tracker_id, 888, 777, 999, 666)->at(0);
        stub($dao)->create($to_tracker_id, 9999, 9998, 9997, 9996)->at(1);
        stub($dao)->create($to_tracker_id, 9999, 9998, 9997, 9995)->at(2);
        $form_factory = mock('Tracker_FormElementFactory');

        $factory = new Tracker_Rule_List_Factory($dao, $form_factory);
        $factory->duplicate($from_tracker_id, $to_tracker_id, $field_mapping);
    }

    public function testExport()
    {
        $xml = simplexml_load_file(dirname(__FILE__) . '/../../../_fixtures/ImportTrackerRulesTest.xml');

        $f1 = stub('Tracker_FormElement_Field_List')->getId()->returns(102);
        $f2 = stub('Tracker_FormElement_Field_List')->getId()->returns(103);

        $form_element_factory = mock('Tracker_FormElementFactory');
        stub($form_element_factory)->getFormElementById(102)->returns($f1);
        stub($form_element_factory)->getFormElementById(103)->returns($f2);

        $bind_f1 = mock('Tracker_FormElement_Field_List_Bind_Static');
        $bind_f2 = mock('Tracker_FormElement_Field_List_Bind_Static');

        stub($f1)->getBind()->returns($bind_f1);
        stub($f2)->getBind()->returns($bind_f2);

        $bf = mock('Tracker_FormElement_Field_List_BindFactory');
        $bf->setReturnValue('getType', 'static', array($bind_f1));
        $bf->setReturnValue('getType', 'static', array($bind_f2));

        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $array_xml_mapping = array('F25' => 102,
                                   'F28' => 103,
                                   'values' => array(
                                       'F25-V1' => 801,
                                       'F25-V2' => 802,
                                       'F25-V3' => 803,
                                       'F25-V4' => 804,
                                       'F28-V1' => 806,
                                       'F28-V2' => 807,
                                       'F28-V3' => 808,
                                       'F28-V4' => 809,
                                   ));

        $r1 = new Tracker_Rule_List(1, 101, 103, 806, 102, 803);
        $r2 = new Tracker_Rule_List(1, 101, 103, 806, 102, 804);

        $trm = partial_mock('Tracker_Rule_List_Factory', array('searchByTrackerId'), array(mock('Tracker_Rule_List_Dao')));
        $trm->setReturnValue('searchByTrackerId', array($r1, $r2));

        $trm->exportToXML($root, $array_xml_mapping, $form_element_factory, 666);
        $this->assertNull($root->dependencies->rule);
    }
}
