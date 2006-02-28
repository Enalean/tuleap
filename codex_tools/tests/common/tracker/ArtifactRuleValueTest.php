<?php
//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('tests/CodexReporter.class');
}

require_once('tests/simpletest/unit_tester.php');
require_once('tests/simpletest/mock_objects.php'); //uncomment to use Mocks

require_once('common/tracker/ArtifactRuleValue.class');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: ArtifactRuleValueTest.php 1901 2005-08-18 14:54:55Z nterray $
 *
 * Tests the class ArtifactRuleValue
 */
class ArtifactRuleValueTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function ArtifactRuleValueTest($name = 'ArtifactRuleValue test') {
        $this->UnitTestCase($name);
    }
    
    function testPass() {
        $arv =& new ArtifactRuleValue('id', 'group_artifact_id', 'source_field', 'source_value', 'target_field', 'target_value');
        
        $this->assertTrue( $arv->pass('group_artifact_id',       'source_field',        'source_value',       'target_field',       'target_value'      ));
        $this->assertTrue( $arv->pass('false_group_artifact_id', 'source_field',        'source_value',       'target_field',       'farget_value'      ));
        $this->assertTrue( $arv->pass('false_group_artifact_id', 'source_field',        'source_value',       'target_field',       'false_target_value'));
        $this->assertTrue( $arv->pass('group_artifact_id',       'target_source_field', 'source_value',       'target_field',       'false_value'       ));
        $this->assertTrue( $arv->pass('group_artifact_id',       'target_source_field', 'source_value',       'target_field',       'false_target_value'));
        $this->assertTrue( $arv->pass('group_artifact_id',       'source_field',        'false_source_value', 'target_field',       'false_value'       ));
        $this->assertTrue( $arv->pass('group_artifact_id',       'source_field',        'false_source_value', 'target_field',       'false_target_value'));
        $this->assertTrue( $arv->pass('group_artifact_id',       'source_field',        'source_value',       'false_target_field', 'false_value'       ));
        $this->assertTrue( $arv->pass('group_artifact_id',       'source_field',        'source_value',       'false_target_field', 'false_target_value'));
        
        $this->assertFalse($arv->pass('group_artifact_id',       'source_field',        'source_value',       'target_field',       'false_target_value'));
    }
}

if (CODEX_RUNNER === __FILE__) {
    $test = &new ArtifactRuleValueTest();
    $test->run(new CodexReporter());
 }
?>
