<?php
//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('tests/CodexReporter.class');
}

require_once('tests/simpletest/unit_tester.php');
require_once('tests/simpletest/mock_objects.php'); //uncomment to use Mocks

require_once('common/tracker/ArtifactRuleFactory.class');

//require_once('common/tracker/ArtifactType.class'); //We cannot mock directly ArtifactType because this file cannot be directly included
class ArtifactType {
    function getId() {}
}
Mock::generate('ArtifactType');
require_once('common/dao/DfRulesDao.class');
Mock::generate('DfRulesDao');
require_once('common/dao/DfRulesFieldvaluesDao.class');
Mock::generate('DfRulesFieldvaluesDao');
require_once('common/dao/DfConditionsDao.class');
Mock::generate('DfConditionsDao');
require_once('common/dao/DfConditionsFieldvaluesDao.class');
Mock::generate('DfConditionsFieldvaluesDao');
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
        $rules_values_dar      =& new MockDataAccessResult($this);
        $conditions_dar        =& new MockDataAccessResult($this);
        $conditions_values_dar =& new MockDataAccessResult($this);
        
        $rules_dar->setReturnValue('getRow', array(
            'id'                => 123,
            'group_artifact_id' => 1,
            'field_id'          => 2,
            'condition_id'      => 3
        ));
        $conditions_dar->setReturnValue('getRow', array(
            'id'                => 3,
            'field_id'          => 4
        ));
        $rules_values_dar->setReturnValue('getRow', false);
        $rules_values_dar->setReturnValueAt(0, 'getRow', array('rule_id' => 123, 'value_id' => 10));
        $rules_values_dar->setReturnValueAt(1, 'getRow', array('rule_id' => 123, 'value_id' => 20));
        $rules_values_dar->setReturnValueAt(2, 'getRow', array('rule_id' => 123, 'value_id' => 30));
        $rules_values_dar->setReturnValueAt(3, 'getRow', array('rule_id' => 123, 'value_id' => 40));
        
        $conditions_values_dar->setReturnValue('getRow', false);
        $conditions_values_dar->setReturnValueAt(0, 'getRow', array('condition_id' => 123, 'value_id' => 100));
        $conditions_values_dar->setReturnValueAt(1, 'getRow', array('condition_id' => 123, 'value_id' => 200));
        $conditions_values_dar->setReturnValueAt(2, 'getRow', array('condition_id' => 123, 'value_id' => 300));
        $conditions_values_dar->setReturnValueAt(3, 'getRow', array('condition_id' => 123, 'value_id' => 400));
        
        $rules_dao             =& new MockDfRulesDao($this);
        $rules_values_dao      =& new MockDfRulesFieldvaluesDao($this);
        $conditions_dao        =& new MockDfConditionsDao($this);
        $conditions_values_dao =& new MockDfConditionsFieldvaluesDao($this);
        
        $rules_dao->setReturnReference('searchById', $rules_dar, array(123));
        $conditions_dao->setReturnReference('searchById', $conditions_dar, array(3));
        $rules_values_dao->setReturnReference('searchByRuleId', $rules_values_dar, array(123));
        $conditions_values_dao->setReturnReference('searchByConditionId', $conditions_values_dar, array(3));
        
        $arf =& new ArtifactRuleFactory($rules_dao, $rules_values_dao, $conditions_dao, $conditions_values_dao);
        
        $r =& $arf->getRuleById(123);
        $this->assertIsA($r, 'ArtifactRule');
        $this->assertEqual($r->id, 123);
        $this->assertEqual($r->getField(), 2);
        $this->assertEqual($r->getValues(), array(10, 20, 30, 40));
        $c =& $r->getCondition();
        $this->assertEqual($c->id, 3);
        $this->assertEqual($c->getField(), 4);
        $this->assertEqual($c->getValues(), array(100, 200, 300, 400));
        
        $this->assertFalse($arf->getRuleById(124), 'If id is inexistant, then return will be false');
        
        $this->assertReference($arf->getRuleById(123), $r, 'We do not create two different instances for the same id');
    }
}

if (CODEX_RUNNER === __FILE__) {
    $test = &new ArtifactRuleFactoryTest();
    $test->run(new CodexReporter());
 }
?>
