<?php
require_once('common/include/HTTPRequest.class.php');
Mock::generate('Validator');
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * Tests the class HTTPRequest
 */
class HTTPRequestTest extends UnitTestCase {
    
    
    
    function UnitTestCase($name = 'HTTPRequest test') {
        $this->UnitTestCase($name);
    }

    function setUp() {
        $_REQUEST['exists'] = '1';
        $_REQUEST['exists_empty'] = '';
        $_SERVER['server_exists'] = '1';
        if (get_magic_quotes_gpc()) {
            $_REQUEST['quote'] = "l\\'avion";
            $_REQUEST['array'] = array('quote_1' => "l\\'avion", 'quote_2' => array('quote_3' => "l\\'oiseau"));
            $_SERVER['server_quote'] = "l\\'avion du server";
        } else {
            $_REQUEST['quote'] = "l'avion";
            $_REQUEST['array'] = array('quote_1' => "l'avion", 'quote_2' => array('quote_3' => "l'oiseau"));
            $_SERVER['server_quote'] = "l\'avion du server";
        }
        $_REQUEST['testkey'] = 'testvalue';
    }
    
    function tearDown() {
        unset($_REQUEST['exists']);
        unset($_REQUEST['quote']);
        unset($_REQUEST['exists_empty']);
        unset($_SERVER['server_exists']);
        unset($_SERVER['server_quote']);
        unset($_REQUEST['testkey']);
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

    function testExistAndNonEmpty() {
        $r =& new HTTPRequest();
        $this->assertTrue($r->existAndNonEmpty('exists'));
        $this->assertFalse($r->existAndNonEmpty('exists_empty'));
        $this->assertFalse($r->existAndNonEmpty('does_not_exist'));
    }

    function testQuotes() {
        $r =& new HTTPRequest();
        $this->assertIdentical($r->get('quote'), "l'avion");
    }

    function testServerGet() {
        $r =& new HTTPRequest();
        $this->assertEqual($r->getFromServer('server_exists'), '1');
        $this->assertFalse($r->getFromServer('does_not_exist'));
    }

    function testServerQuotes() {
        $r =& new HTTPRequest();
        $this->assertIdentical($r->getFromServer('server_quote'), "l'avion du server");
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

    function testValidTrue() {
        $v =& new MockValidator();
        $v->setReturnValue('isValid', true);
        $r =& new HTTPRequest();
        $this->assertTrue($r->valid('testkey', $v));
    }

    function testValidFalse() {
        $v =& new MockValidator();
        $v->setReturnValue('isValid', false);
        $r =& new HTTPRequest();
        $this->assertFalse($r->valid('testkey', $v));
    }

    function testValidScalar() {
        $v =& new MockValidator();
        $v->expectOnce('isValid', array('testvalue'));
        $r =& new HTTPRequest();
        $r->valid('testkey', $v);
        $v->tally();
    }

}
?>
