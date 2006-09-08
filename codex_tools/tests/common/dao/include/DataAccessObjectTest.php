<?php
//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('tests/CodexReporter.class');
}

require_once('tests/simpletest/unit_tester.php');
require_once('tests/simpletest/mock_objects.php');
require_once('common/dao/include/DataAccessObject.class');
require_once('common/dao/include/DataAccess.class');
Mock::generate('DataAccess');
require_once('common/dao/include/DataAccessResult.class');
Mock::generate('DataAccessResult');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: DaoTest.php,v 1.2 2005/08/01 14:29:51 nterray Exp $
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
        require(getenv('CODEX_LOCAL_INC'));
        $da =& new DataAccess($sys_dbhost, $sys_dbuser, $sys_dbpasswd, $sys_dbname);
        $this->assertFalse($da->isError());
        $dao =& new DataAccessObject($da);
        $result =& $dao->retrieve("SELECT (4+1)*5 as calcul");
        $row =& $result->getRow();
        $this->assertEqual($row['calcul'], 25);
    }
}

if (CODEX_RUNNER === __FILE__) {
    $test = &new DaoTest();
    $test->run(new CodexReporter());
 }
?>
