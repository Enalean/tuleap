<?php
require('common/dao/include/DataAccessObject.class.php');
require('common/dao/include/DataAccess.class.php');
Mock::generate('DataAccess');
require_once('common/dao/include/DataAccessResult.class.php');
Mock::generate('DataAccessResult');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id$
 *
 * Tests the class Dao
 */
class DaoTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function DaoTest($name = 'Dao test') {
        $this->UnitTestCase($name);
    }

    function testDao() {
        $da =& new MockDataAccess($this);
        $dar =& new MockDataAccessResult($this);
        $da->setReturnReference('fetch', $dar);
        $dao =& new DataAccessObject($da);
        
        $result =& $dao->retrieve("SELECT *");
        $this->assertIsA($result, 'MockDataAccessResult');
    }
    
    function testRealDao() {
        $da =& new DataAccess($GLOBALS['sys_dbhost'], $GLOBALS['sys_dbuser'], $GLOBALS['sys_dbpasswd'], $GLOBALS['sys_dbname']);
        $this->assertFalse($da->isError());
        $dao =& new DataAccessObject($da);
        $result =& $dao->retrieve("SELECT (4+1)*5 as calcul");
        $row =& $result->getRow();
        $this->assertEqual($row['calcul'], 25);
    }
}
?>
