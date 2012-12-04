<?php

require_once(dirname(__FILE__).'/../include/constants.php');
require_once(dirname(__FILE__).'/builders/all.php');
require_once(dirname(__FILE__).'/../include/Tracker/Rule/Tracker_RuleFactory.class.php');

require_once(dirname(__FILE__).'/../include/Tracker/Rule/dao/Tracker_RuleDao.class.php');
Mock::generate('Tracker_RuleDao');

require_once('common/dao/include/DataAccessResult.class.php');
Mock::generate('DataAccessResult');

require_once(dirname(__FILE__).'/../include/Tracker/Tracker.class.php');
Mock::generate('Tracker');

require_once(dirname(__FILE__).'/../include/Tracker/FormElement/Tracker_FormElement_Field_List.class.php');
Mock::generate('Tracker_FormElement_Field_List');
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 *
 *
 * Tests the class ArtifactRuleFactory
 */
class Tracker_RuleFactoryTest extends UnitTestCase {

    public function testImport() {
        $xml = simplexml_load_file(dirname(__FILE__) . '/_fixtures/ImportTrackerRulesTest.xml');
        $tracker = aTracker()->withId(666)->build();

        $f1 = stub('Tracker_FormElement_Field_List')->getId()->returns(102);
        $f2 = stub('Tracker_FormElement_Field_List')->getId()->returns(103);

        $array_xml_mapping = array('F25' => 102,
                                   'F28' => 103,
                                   'F25-V1' => 801,
                                   'F25-V2' => 802,
                                   'F25-V3' => 803,
                                   'F25-V4' => 804,
                                   'F28-V1' => 806,
                                   'F28-V2' => 807,
                                   'F28-V3' => 808,
                                   'F28-V4' => 809,
                                   );
        $tracker_rule_dao = mock('Tracker_RuleDao');
        $rule_factory = new Tracker_RuleFactory($tracker_rule_dao);
        $rules = $rule_factory->getInstanceFromXML($xml, $array_xml_mapping, $tracker);

        $target_value_expected  = new Tracker_Rule_List();
        $target_value_expected->setSourceValue($array_xml_mapping['F28-V1'])
                ->setTargetValue($array_xml_mapping['F25-V3'])
                ->setId(0)
                ->setTrackerId($tracker->id)
                ->setSourceFieldId($array_xml_mapping['F28'])
                ->setTargetFieldId($array_xml_mapping['F25']);
        
        $target_value_expected2 = new Tracker_Rule_List();
        $target_value_expected2->setSourceValue($array_xml_mapping['F28-V1'])
                ->setTargetValue($array_xml_mapping['F25-V4'])
                ->setId(0)
                ->setTrackerId($tracker->id)
                ->setSourceFieldId($array_xml_mapping['F28'])
                ->setTargetFieldId($array_xml_mapping['F25']);
        
        $this->assertEqual(count($rules), 2);
        $this->assertEqual($rules[0], $target_value_expected);
        $this->assertEqual($rules[1], $target_value_expected2);
    }
    
    public function testDuplicateCallsListAndDateDuplicate() {
        $from_tracker_id = 10;
        $to_tracker_id   = 53;
        $field_mapping   = array();
        
        $rule_list_factory = mock('Tracker_Rule_List_Factory');
        $rule_date_factory = mock('Tracker_Rule_Date_Factory');
        $rule_factory = partial_mock(
                'Tracker_RuleFactory', 
                array(
                    'getListFactory', 
                    'getDateFactory',)
                );
        
        stub($rule_factory)->getListFactory()->returns($rule_list_factory);
        stub($rule_factory)->getDateFactory()->returns($rule_date_factory);
        
        expect($rule_list_factory)->duplicate($from_tracker_id, $to_tracker_id, $field_mapping)->once();
        $rule_factory->duplicate($from_tracker_id, $to_tracker_id, $field_mapping);
    }
    
    public function testGetListOrDateFactoriesReturnNewInstancesWhenNotSet() {
        $list_dao = mock('Tracker_Rule_List_Dao');
        $date_dao = mock('Tracker_Rule_Date_Dao');
        $rule_dao = mock('Tracker_RuleDao');
        
        $factory =  new Tracker_RuleFactory($rule_dao);
        $factory->setListDao($list_dao);
        $factory->setDateDao($date_dao);
        
        $this->assertIsA($factory->getListFactory(), 'Tracker_Rule_List_Factory');
        $this->assertIsA($factory->getDateFactory(), 'Tracker_Rule_Date_Factory');
    }
    
    public function testSaveObjectCallsDateAndListFactorySaveObjectMethods() {
        $rule_dao = mock('Tracker_RuleDao');
        $list_rules= array();
        $date_rules= array();

        $rules = array(
            'list_rules' => $list_rules,
            'date_rules' => $date_rules,
        );
        
        $tracker = mock('Tracker');
        
        $date_factory = mock('Tracker_Rule_Date_Factory');
        stub($date_factory)->saveObject($date_rules, $tracker)->once();
        
        $list_factory = mock('Tracker_Rule_List_Factory');
        stub($list_factory)->saveObject($list_rules, $tracker)->once();
        
        $factory =  new Tracker_RuleFactory($rule_dao);
        $factory->setListFactory($list_factory);
        $factory->setDateFactory($date_factory);
        
        $factory->saveObject($rules, $tracker);     
    }
}
?>