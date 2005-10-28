<?php
//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('tests/CodexReporter.class');
}

require_once('tests/simpletest/unit_tester.php');
require_once('common/include/Request.class');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: RequestTest.php 1901 2005-08-18 14:54:55Z nterray $
 *
 * Tests the class Request
 */
class RequestTest extends UnitTestCase {
    
    
    
    function UnitTestCase($name = 'Request test') {
        $this->UnitTestCase($name);
    }

    function setUp() {
        $_REQUEST['exists'] = '1';
        if (get_magic_quotes_gpc()) {
            $_REQUEST['quote'] = 'l\\\'avion';
        } else {
            $_REQUEST['quote'] = 'l\'avion';
        }
    }
    
    function tearDown() {
        unset($_REQUEST['exists']);
        unset($_REQUEST['quote']);
    }
    
    function testGet() {
        $r =& new Request();
        $this->assertEqual($r->get('exists'), '1');
        $this->assertFalse($r->get('does_not_exist'));
    }
    
    function testSetted() {
        $r =& new Request();
        $this->assertTrue($r->setted('exists'));
        $this->assertFalse($r->setted('does_not_exist'));
    }
    
    function testQuotes() {
        $r =& new Request();
        $this->assertIdentical($r->get('quote'), 'l\'avion');
    }

}

if (CODEX_RUNNER === __FILE__) {
    $test = &new RequestTest();
    $test->run(new CodexReporter());
 }
?>
