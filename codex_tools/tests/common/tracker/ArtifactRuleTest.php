<?php
//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('tests/CodexReporter.class');
}

require_once('tests/simpletest/unit_tester.php');
require_once('tests/simpletest/mock_objects.php'); //uncomment to use Mocks

require_once('common/tracker/ArtifactRule.class');
Mock::generate('ArtifactCondition');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: ArtifactRuleTest.php 1901 2005-08-18 14:54:55Z nterray $
 *
 * Tests the class ArtifactRule
 */
class ArtifactRuleTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function ArtifactRuleTest($name = 'ArtifactRule test') {
        $this->UnitTestCase($name);
    }
    
}

if (CODEX_RUNNER === __FILE__) {
    $test = &new ArtifactRuleTest();
    $test->run(new CodexReporter());
 }
?>
