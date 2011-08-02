<?php

require_once(dirname(__FILE__).'/../include/Tracker_RuleFactory.class.php');

require_once(dirname(__FILE__).'/../include/dao/Tracker_RuleDao.class.php');
Mock::generate('Tracker_RuleDao');

require_once('common/dao/include/DataAccessResult.class.php');
Mock::generate('DataAccessResult');
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * Tests the class ArtifactRuleFactory
 */
class Tracker_RuleFactoryTest extends UnitTestCase {


    function testGetRuleById() {
        $rules_dar             = new MockDataAccessResult();
        $rules_dar->setReturnValue('getRow', array(
            'id'                => 123,
            'tracker_id'        => 1,
            'source_field_id'   => 2,
            'source_value_id'   => 10,
            'target_field_id'   => 4,
            'rule_type'         => 4, //RuleValue
            'target_value_id'   => 100
        ));
        
        $rules_dao             = new MockTracker_RuleDao();
        $rules_dao->setReturnReference('searchById', $rules_dar, array(123));
        
        $arf = new Tracker_RuleFactory($rules_dao);
        
        $r = $arf->getRuleById(123);
        $this->assertIsA($r, 'Tracker_Rule');
        $this->assertIsA($r, 'Tracker_Rule_Value');
        $this->assertEqual($r->id, 123);
        $this->assertEqual($r->tracker_id, 1);
        $this->assertEqual($r->source_field, 2);
        $this->assertEqual($r->target_field, 4);
        $this->assertEqual($r->source_value, 10);
        $this->assertEqual($r->target_value, 100);
        
        $this->assertFalse($arf->getRuleById(124), 'If id is inexistant, then return will be false');
        
        $this->assertReference($arf->getRuleById(123), $r, 'We do not create two different instances for the same id');
    }
}
?>
