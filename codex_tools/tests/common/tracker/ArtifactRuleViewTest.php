<?php
//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('tests/CodexReporter.class');
}

require_once('tests/simpletest/unit_tester.php');
require_once('tests/simpletest/mock_objects.php'); //uncomment to use Mocks
require_once('common/tracker/ArtifactRuleView.class');
require_once('common/tracker/ArtifactRule.class');
Mock::generate('ArtifactRule');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: ArtifactRuleViewTest.php 1901 2005-08-18 14:54:55Z nterray $
 *
 * Tests the class ArtifactRuleView
 */
class ArtifactRuleViewTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function ArtifactRuleViewTest($name = 'ArtifactRuleView test') {
        $this->UnitTestCase($name);
    }

    function testFetch() {
        $rule =& new MockArtifactRule($this);
        $rule->id                = 'id';
        $rule->group_artifact_id = 'group_artifact_id';
        $rule->source_field      = 'source_field';
        $rule->source_value      = 'source_value';
        $rule->target_field      = 'target_field';
        $rule->target_values     = array('target_value_1', 'target_value_2');

        $view =& new ArtifactRuleView($rule);
        $this->assertEqual($view->fetch(), '#id@group_artifact_id source_field(source_value) => target_field(target_value_1, target_value_2)');
    }
}

if (CODEX_RUNNER === __FILE__) {
    $test = &new ArtifactRuleViewTest();
    $test->run(new CodexReporter());
 }
?>
