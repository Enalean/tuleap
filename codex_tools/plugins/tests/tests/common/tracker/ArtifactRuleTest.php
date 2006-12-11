<?php
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
?>
