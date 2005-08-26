<?php
//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('tests/CodexReporter.class');
}

require_once('tests/simpletest/unit_tester.php');
//require_once('tests/simpletest/mock_objects.php'); //uncomment to use Mocks
require_once('common/plugin/PluginDescriptor.class');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: PluginDescriptorTest.php 1901 2005-08-18 14:54:55Z nterray $
 *
 * Tests the class PluginDescriptor
 */
class PluginDescriptorTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function PluginDescriptorTest($name = 'PluginDescriptor test') {
        $this->UnitTestCase($name);
    }

    function testIconPath() {
        $pd =& new PluginDescriptor();
        $this->assertEqual($pd->getEnabledIconPath(), '/themes/codex/images/plugin_icon.png');
        $this->assertEqual($pd->getDisabledIconPath(), '/themes/codex/images/plugin_icon_disabled.png');
    }
}

if (CODEX_RUNNER === __FILE__) {
    $test = &new PluginDescriptorTest();
    $test->run(new CodexReporter());
 }
?>
