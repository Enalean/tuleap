<?php

require_once('bootstrap.php');
Mock::generate('Tracker_RuleDao');

require_once('common/dao/include/DataAccessResult.class.php');
Mock::generate('DataAccessResult');

Mock::generate('Tracker');

Mock::generate('Tracker_FormElement_Field_List');
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 *
 *
 * Tests the class ArtifactRuleFactory
 */
class Tracker_RuleFactoryTest extends UnitTestCase {

    public function testImportListRules() {
        $xmlstr = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
    <rules>
    </rules>
XML;
        $xml = new SimpleXMLElement($xmlstr);
        
        $list_rules = $xml->addChild('list_rules');
        
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

        $tracker = aTracker()->withId(666)->build();
        $f1 = stub('Tracker_FormElement_Field_List')->getId()->returns(102);
        $f2 = stub('Tracker_FormElement_Field_List')->getId()->returns(103);
        $f3 = stub('Tracker_FormElement_Field_List')->getId()->returns(104);
        $f4 = stub('Tracker_FormElement_Field_List')->getId()->returns(105);
        $f5 = stub('Tracker_FormElement_Field_List')->getId()->returns(106);
        
        $array_xml_mapping = array('F25' => $f1,
                                   'F28' => $f2,
                                   'F25-V3' => $f4,
                                   'F25-V4' => $f5,
                                   'F28-V1' => $f3,
                                   );
        
        
        $tracker_rule_dao = mock('Tracker_RuleDao');
        $rule_factory = new Tracker_RuleFactory($tracker_rule_dao);
        $rules = $rule_factory->getInstanceFromXML($xml, $array_xml_mapping, $tracker);

        $list_rule_expected  = new Tracker_Rule_List();
        $list_rule_expected->setSourceValue($array_xml_mapping['F28-V1'])
                ->setTargetValue($array_xml_mapping['F25-V3'])
                ->setId(0)
                ->setTrackerId($tracker->getId())
                ->setSourceField($array_xml_mapping['F28'])
                ->setTargetField($array_xml_mapping['F25']);
        
        $list_rule_expected2 = new Tracker_Rule_List();
        $list_rule_expected2->setSourceValue($array_xml_mapping['F28-V1'])
                ->setTargetValue($array_xml_mapping['F25-V4'])
                ->setId(0)
                ->setTrackerId($tracker->getId())
                ->setSourceField($array_xml_mapping['F28'])
                ->setTargetField($array_xml_mapping['F25']);
        
        $this->assertEqual(count($rules['list_rules']), 2);
        $this->assertEqual($rules['list_rules'][0], $list_rule_expected);
        $this->assertEqual($rules['list_rules'][1], $list_rule_expected2);
    }
    
    public function testImportDateRules() {
        $xmlstr = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
    <rules>
    </rules>
XML;
        $xml = new SimpleXMLElement($xmlstr);
        
        $date_rules = $xml->addChild('date_rules');
        
        $rule1 = $date_rules->addChild('rule');
        $rule1->addChild('source_field')->addAttribute('REF', 'F28');
        $rule1->addChild('target_field')->addAttribute('REF', 'F25');
        $rule1->addChild('comparator')->addAttribute('type', Tracker_rule_date::COMPARATOR_NOT_EQUALS);
        
        $rule2 = $date_rules->addChild('rule');
        $rule2->addChild('source_field')->addAttribute('REF', 'F29');
        $rule2->addChild('target_field')->addAttribute('REF', 'F30');
        $rule2->addChild('comparator')->addAttribute('type', Tracker_rule_date::COMPARATOR_LESS_THAN_OR_EQUALS);

        $tracker = mock('Tracker');
        stub($tracker)->getId()->returns(900);
        $f1 = stub('Tracker_FormElement_Field_Date')->getId()->returns(102);
        $f2 = stub('Tracker_FormElement_Field_Date')->getId()->returns(103);
        $f3 = stub('Tracker_FormElement_Field_Date')->getId()->returns(104);
        $f4 = stub('Tracker_FormElement_Field_Date')->getId()->returns(105);
        
        $array_xml_mapping = array(
            'F25' => $f3,
            'F28' => $f1,
            'F29' => $f2,
            'F30' => $f4,
            );
        
        $tracker_rule_dao = mock('Tracker_RuleDao');
        $rule_factory = new Tracker_RuleFactory($tracker_rule_dao);
        $rules = $rule_factory->getInstanceFromXML($xml, $array_xml_mapping, $tracker);

        $date_rule_expected  = new Tracker_Rule_Date();
        $date_rule_expected->setComparator(Tracker_rule_date::COMPARATOR_NOT_EQUALS)
                ->setTrackerId($tracker->getId())
                ->setSourceField($array_xml_mapping['F28'])
                ->setTargetField($array_xml_mapping['F25']);
        
        $date_rule_expected2 = new Tracker_Rule_Date();
        $date_rule_expected2->setComparator(Tracker_rule_date::COMPARATOR_LESS_THAN_OR_EQUALS)
                ->setTrackerId($tracker->getId())
                ->setSourceField($array_xml_mapping['F29'])
                ->setTargetField($array_xml_mapping['F30']);

        $this->assertEqual(count($rules['date_rules']), 2);
        $this->assertEqual($rules['date_rules'][0], $date_rule_expected);
        $this->assertEqual($rules['date_rules'][1], $date_rule_expected2);
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
    
    public function testSaveObjectCallsDateAndListFactoryInsertMethods() {
        $rule_dao = mock('Tracker_RuleDao');
        $list = mock('Tracker_Rule_List');
        $date = mock('Tracker_Rule_Date');
        
        $list_rules= array(
            $list,
        );
        $date_rules= array(
            $date,
        );

        $rules = array(
            'list_rules' => $list_rules,
            'date_rules' => $date_rules,
        );
        
        $tracker = mock('Tracker');
        
        $date_factory = mock('Tracker_Rule_Date_Factory');
        stub($date_factory)->insert($date)->once();
        
        $list_factory = mock('Tracker_Rule_List_Factory');
        stub($list_factory)->insert($list)->once();
        
        $factory =  new Tracker_RuleFactory($rule_dao);
        $factory->setListFactory($list_factory);
        $factory->setDateFactory($date_factory);
        
        $factory->saveObject($rules, $tracker);     
    }
    
    public function testSaveObjectCallsDateInsertMethodWhenNoListRulesAreInArray() {
        $rule_dao = mock('Tracker_RuleDao');
        $date = mock('Tracker_Rule_Date');
        
        $date_rules= array(
            $date,
        );
        $rules = array(
            'date_rules' => $date_rules,
        );
        
        $tracker = mock('Tracker');
  
        $date_factory = mock('Tracker_Rule_Date_Factory');
        stub($date_factory)->insert($date)->once();

        $factory =  new Tracker_RuleFactory($rule_dao);
        $factory->setDateFactory($date_factory);
        
        $factory->saveObject($rules, $tracker);     
    }
    
    public function testSaveObjectCallsListInsertMethodWhenNoDateRulesAreInArray() {
        $rule_dao = mock('Tracker_RuleDao');
        $list = mock('Tracker_Rule_List');
        
        $list_rules= array(
            $list,
        );

        $rules = array(
            'list_rules' => $list_rules,
        );
        
        $tracker = mock('Tracker');

        $list_factory = mock('Tracker_Rule_List_Factory');
        stub($list_factory)->insert($list)->once();
        
        $factory =  new Tracker_RuleFactory($rule_dao);
        $factory->setListFactory($list_factory);

        $factory->saveObject($rules, $tracker);     
    }
}
?>