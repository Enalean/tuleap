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
class DaoTest extends TuleapTestCase {

    function testDao() {
        $da = new MockDataAccess($this);
        $dar = new MockDataAccessResult($this);
        $da->setReturnReference('query', $dar);
        $dao = new DataAccessObject($da);
        
        $result = $dao->retrieve("SELECT *");
        $this->assertIsA($result, 'MockDataAccessResult');
    }
}
?>
