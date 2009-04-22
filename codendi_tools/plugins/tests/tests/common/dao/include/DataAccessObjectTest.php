<?php
require_once('common/dao/include/DataAccessObject.class.php');
require_once('common/dao/include/DataAccess.class.php');
Mock::generate('DataAccess');
require_once('common/dao/include/DataAccessResult.class.php');
Mock::generate('DataAccessResult');

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
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
}
?>
