<?php

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * Tests legacy account_
 */

require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');

require_once("account.php");

class AccountTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function AccountTest($name = 'Account test') {
        $this->UnitTestCase($name);
    }

    function setUp() {
        $GLOBALS['Language'] = new MockBaseLanguage($this);
    }
    function tearDown() {
        unset($GLOBALS['Language']);
    }
    function testEquals() {
        $this->assertTrue(account_namevalid("abcdef"));
        $this->assertTrue(account_namevalid("abcdef123"));
        $this->assertTrue(account_namevalid("abc"));
        $this->assertTrue(account_namevalid("abc456789012345678901234567890"));
        $this->assertTrue(account_namevalid("abc-def_"));
        $this->assertTrue(account_namevalid("abc.def"));

        $this->assertFalse(account_namevalid("abc4567890123456789012345678901"));
        $this->assertFalse(account_namevalid("abc def"));
        $this->assertFalse(account_namevalid("abc/def"));
        $this->assertFalse(account_namevalid("abc=def"));
        $this->assertFalse(account_namevalid("abc:def"));
        $this->assertFalse(account_namevalid("a"));
        $this->assertFalse(account_namevalid("ab"));
        $this->assertFalse(account_namevalid("1abcdef"));
        $this->assertFalse(account_namevalid("debian"));
        $this->assertFalse(account_namevalid("anoncvs_toto"));
       //$this->assertEqual($s1->hashCode(), $s2->hashCode());
        //$this->assertEqual($s2->compareTo($s1), 1);
    }
}
?>
