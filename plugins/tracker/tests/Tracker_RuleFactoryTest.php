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

        $target_value_expected  = new Tracker_Rule_List(0,$tracker->id,$array_xml_mapping['F28'],$array_xml_mapping['F28-V1'],$array_xml_mapping['F25'],$array_xml_mapping['F25-V3']);
        $target_value_expected2 = new Tracker_Rule_List(0,$tracker->id,$array_xml_mapping['F28'],$array_xml_mapping['F28-V1'],$array_xml_mapping['F25'],$array_xml_mapping['F25-V4']);

        $this->assertEqual(count($rules), 2);
        $this->assertEqual($rules[0], $target_value_expected);
        $this->assertEqual($rules[1], $target_value_expected2);
    }
}
?>