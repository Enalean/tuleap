<?php

require_once('common/language/BaseLanguage.class.php');
$GLOBALS['Language'] = new BaseLanguage();

require_once(dirname(__FILE__).'/../include/IMPlugin.class.php');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * Test the class IMPlugin
 */
class IMPluginTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function IMPluginTest($name = 'IMPlugin test') {
        $this->UnitTestCase($name);
    }
    
    function testTrue() {
        $this->assertTrue(true);
    }
    
}

?>
