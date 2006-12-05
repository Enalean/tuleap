<?php
require_once('common/include/HTTPRequest.class');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: HTTPRequestTest.php 1901 2005-08-18 14:54:55Z nterray $
 *
 * Tests the class HTTPRequest
 */
class HTTPRequestTest extends UnitTestCase {
    
    
    
    function UnitTestCase($name = 'HTTPRequest test') {
        $this->UnitTestCase($name);
    }

    function setUp() {
        $_REQUEST['exists'] = '1';
        if (get_magic_quotes_gpc()) {
            $_REQUEST['quote'] = "l\\'avion";
            $_REQUEST['array'] = array('quote_1' => "l\\'avion", 'quote_2' => array('quote_3' => "l\\'oiseau"));
        } else {
            $_REQUEST['quote'] = "l'avion";
            $_REQUEST['array'] = array('quote_1' => "l'avion", 'quote_2' => array('quote_3' => "l'oiseau"));
        }
    }
    
    function tearDown() {
        unset($_REQUEST['exists']);
        unset($_REQUEST['quote']);
    }
    
    function testGet() {
        $r =& new HTTPRequest();
        $this->assertEqual($r->get('exists'), '1');
        $this->assertFalse($r->get('does_not_exist'));
    }
    
    function testExist() {
        $r =& new HTTPRequest();
        $this->assertTrue($r->exist('exists'));
        $this->assertFalse($r->exist('does_not_exist'));
    }
    
    function testQuotes() {
        $r =& new HTTPRequest();
        $this->assertIdentical($r->get('quote'), "l'avion");
    }
    
    function testSingleton() {
        $this->assertReference(
                HTTPRequest::instance(),
                HTTPRequest::instance());
        $this->assertIsA(HTTPRequest::instance(), 'HTTPRequest');
    }
    
    function testArray() {
        $r =& new HTTPRequest();
        $this->assertIdentical($r->get('array'), array('quote_1' => "l'avion", 'quote_2' => array('quote_3' => "l'oiseau")));
    }

}
?>
