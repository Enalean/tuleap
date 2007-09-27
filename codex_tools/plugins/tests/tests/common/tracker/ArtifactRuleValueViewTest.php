<?php
require_once('common/tracker/ArtifactRuleValueView.class.php');
require_once('common/tracker/ArtifactRuleValue.class.php');
Mock::generate('ArtifactRuleValue');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: ArtifactRuleValueViewTest.php 1901 2005-08-18 14:54:55Z nterray $
 *
 * Tests the class ArtifactRuleValueView
 */
class ArtifactRuleValueViewTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function ArtifactRuleValueViewTest($name = 'ArtifactRuleValueView test') {
        $this->UnitTestCase($name);
    }

    function testFetch() {
        $rule =& new MockArtifactRuleValue($this);
        $rule->id                = 'id';
        $rule->group_artifact_id = 'group_artifact_id';
        $rule->source_field      = 'source_field';
        $rule->target_field      = 'target_field';
        $rule->source_value      = 'source_value_1';
        $rule->target_value      = 'target_value_2';

        $view =& new ArtifactRuleValueView($rule);
        $this->assertEqual($view->fetch(), '#id@group_artifact_id source_field(source_value_1) => target_field(target_value_2)');
    }
}
?>
