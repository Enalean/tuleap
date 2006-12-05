<?php
require_once('common/tracker/ArtifactRuleFactory.class');

require_once('common/dao/ArtifactRuleDao.class');
Mock::generate('ArtifactRuleDao');
require_once('common/dao/include/DataAccessResult.class');
Mock::generate('DataAccessResult');
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id$
 *
 * Tests the class ArtifactRuleFactory
 */
class ArtifactRuleFactoryTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function ArtifactRuleFactoryTest($name = 'ArtifactRuleFactory test') {
        $this->UnitTestCase($name);
    }

    function testGetRuleById() {
        
        $rules_dar             =& new MockDataAccessResult($this);
        $rules_dar->setReturnValue('getRow', array(
            'id'                => 123,
            'group_artifact_id' => 1,
            'source_field_id'   => 2,
            'source_value_id'   => 10,
            'target_field_id'   => 4,
            'rule_type'         => 4, //RuleValue
            'target_value_id'   => 100
        ));
        
        $rules_dao             =& new MockArtifactRuleDao($this);
        $rules_dao->setReturnReference('searchById', $rules_dar, array(123));
        
        $arf =& new ArtifactRuleFactory($rules_dao);
        
        $r =& $arf->getRuleById(123);
        $this->assertIsA($r, 'ArtifactRule');
        $this->assertIsA($r, 'ArtifactRuleValue');
        $this->assertEqual($r->id, 123);
        $this->assertEqual($r->source_field, 2);
        $this->assertEqual($r->target_field, 4);
        $this->assertEqual($r->source_value, 10);
        $this->assertEqual($r->target_value, 100);
        
        $this->assertFalse($arf->getRuleById(124), 'If id is inexistant, then return will be false');
        
        $this->assertReference($arf->getRuleById(123), $r, 'We do not create two different instances for the same id');
    }
}
?>
