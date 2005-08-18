<?php
//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('tests/CodexReporter.class');
}

require_once('tests/simpletest/unit_tester.php');
//require_once('tests/simpletest/mock_objects.php'); //uncomment to use Mocks
require_once('common/dao/include/DataAccess.class');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: DataAccessTest.php,v 1.2 2005/08/01 14:29:51 nterray Exp $
 *
 * Tests the class DataAccess
 */
class DataAccessTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function DataAccessTest($name = 'DataAccess test') {
        $this->UnitTestCase($name);
    }

    function testConnection() {
        $sys_dbhost   = 'host';
        $sys_dbuser   = 'user';
        $sys_dbpasswd = 'pass';
        $sys_dbname   = 'db';
        $da =& new DataAccess($sys_dbhost, $sys_dbuser, $sys_dbpasswd, $sys_dbname);
        $this->assertEqual($da->isError(), "Unknown MySQL Server Host '".$sys_dbhost."' (1)");
        require(getenv('SF_LOCAL_INC_PREFIX').'/etc/codex/conf/local.inc');
        $sys_dbname_2 = 'db that does not exist';
        $da =& new DataAccess($sys_dbhost, $sys_dbuser, $sys_dbpasswd, $sys_dbname_2);
        $this->assertEqual($da->isError(), "Unknown database '".$sys_dbname_2."'");
        $da =& new DataAccess($sys_dbhost, $sys_dbuser, $sys_dbpasswd, $sys_dbname);
        $this->assertFalse($da->isError());
        $this->assertIsA($da->fetch("select *"),'DataAccessResult');
    }
}

if (CODEX_RUNNER === __FILE__) {
    $test = &new DataAccessTest();
    $test->run(new CodexReporter());
 }
?>
