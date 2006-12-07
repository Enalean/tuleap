<?php
require_once('common/include/ReferenceManager.class.php');
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: ReferenceManagerTest.php 1901 2005-08-18 14:54:55Z nterray $
 *
 * Tests the class ReferenceManager
 */
class ReferenceManagerTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function ReferenceManagerTest($name = 'ReferenceManager test') {
        $this->UnitTestCase($name);
    }

    function testSingleton() {
        $this->assertReference(
                ReferenceManager::instance(),
                ReferenceManager::instance());
        $this->assertIsA(ReferenceManager::instance(), 'ReferenceManager');
    }


    function testKeyword() {
        //The Reference manager
        $rm =& new ReferenceManager();
        $this->assertFalse($rm->_isValidKeyword("UPPER"));
        $this->assertFalse($rm->_isValidKeyword("with space"));
        $this->assertFalse($rm->_isValidKeyword("with_special_char"));
        $this->assertFalse($rm->_isValidKeyword('with$pecialchar'));
        $this->assertFalse($rm->_isValidKeyword("with/special/char"));
        $this->assertFalse($rm->_isValidKeyword("with-special"));
        $this->assertFalse($rm->_isValidKeyword("-begin"));
        $this->assertFalse($rm->_isValidKeyword("end-"));
        $this->assertFalse($rm->_isValidKeyword("end "));

        $this->assertTrue($rm->_isValidKeyword("valid"));
        $this->assertTrue($rm->_isValidKeyword("valid123"));
        $this->assertTrue($rm->_isValidKeyword("123")); // should it be?

        $this->assertTrue($rm->_isReservedKeyword("art"));
        $this->assertTrue($rm->_isReservedKeyword("cvs"));
        $this->assertFalse($rm->_isReservedKeyword("artifacts"));
        $this->assertFalse($rm->_isReservedKeyword("john2"));
  }
}
?>
