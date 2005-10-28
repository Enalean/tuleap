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
        $this->assertWantedPattern("/Unknown MySQL Server Host '".$sys_dbhost."'/",$da->isError());
        require(getenv('SF_LOCAL_INC_PREFIX').'/etc/codex/conf/local.inc');
        $sys_dbname_2 = 'db that does not exist';
        $da =& new DataAccess($sys_dbhost, $sys_dbuser, $sys_dbpasswd, $sys_dbname_2);
        $this->assertEqual($da->isError(), "Unknown database '".$sys_dbname_2."'");
        $da =& new DataAccess($sys_dbhost, $sys_dbuser, $sys_dbpasswd, $sys_dbname);
        $this->assertFalse($da->isError());
        $this->assertIsA($da->fetch("select *"),'DataAccessResult');
    }
    
    function testQuoteSmart() {
        require(getenv('SF_LOCAL_INC_PREFIX').'/etc/codex/conf/local.inc');
        $da =& new DataAccess($sys_dbhost, $sys_dbuser, $sys_dbpasswd, $sys_dbname);
        $this->assertIdentical('123', $da->quoteSmart("123"), "An integer is not quoted");
        $this->assertIdentical('12.3', $da->quoteSmart("12.3"), "A float is not quoted");
        $this->assertIdentical("'value'", $da->quoteSmart("value"), "A string is quoted");
        /* Comment: We do not have addslashed value anymore ! (due to Request object)
        if (get_magic_quotes_gpc()) {
            $this->assertIdentical('\'evil\\\'s value\'', $da->quoteSmart("evil\\'s value"));
            $this->assertIdentical("'\\\\x00'", $da->quoteSmart('\\\\x00'));
            $this->assertIdentical("'\\\\n'", $da->quoteSmart('\\\\n'));
            $this->assertIdentical("'\\\\r'", $da->quoteSmart("\\\\r"));
            $this->assertIdentical("'\\\\'", $da->quoteSmart("\\\\"));
            $this->assertIdentical("'\\''", $da->quoteSmart("\\'"));
            $this->assertIdentical("'\\\"'", $da->quoteSmart("\\\""));
            $this->assertIdentical("'\\\\x1a'", $da->quoteSmart("\\\\x1a"));
        } else {
        */
            $this->assertIdentical("'evil\\'s value'", $da->quoteSmart("evil's value"));
            $this->assertIdentical("'\\\\x00'", $da->quoteSmart('\\x00'));
            $this->assertIdentical("'\\\\n'", $da->quoteSmart('\\n'));
            $this->assertIdentical("'\\\\r'", $da->quoteSmart("\\r"));
            $this->assertIdentical("'\\\\'", $da->quoteSmart("\\"));
            $this->assertIdentical("'\\''", $da->quoteSmart("'"));
            $this->assertIdentical("'\\\"'", $da->quoteSmart("\""));
            $this->assertIdentical("'\\\\x1a'", $da->quoteSmart("\\x1a"));
        /*}*/
    }
}

if (CODEX_RUNNER === __FILE__) {
    $test = &new DataAccessTest();
    $test->run(new CodexReporter());
 }
?>
