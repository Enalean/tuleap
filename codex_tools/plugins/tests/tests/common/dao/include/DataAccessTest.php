<?php
require_once('common/dao/include/DataAccess.class.php');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id$
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
        /*
        $sys_dbhost   = 'host';
        $sys_dbuser   = 'user';
        $sys_dbpasswd = 'pass';
        $sys_dbname   = 'db';
        $da =& new DataAccess($sys_dbhost, $sys_dbuser, $sys_dbpasswd, $sys_dbname);
        $this->assertWantedPattern("/Unknown MySQL Server Host '".$sys_dbhost."'/",$da->isError());
        */
        require(getenv('CODEX_LOCAL_INC'));
        require($GLOBALS['db_config_file']);
        $sys_dbname_2 = 'db that does not exist';
        $da =& new DataAccess($sys_dbhost, $sys_dbuser, $sys_dbpasswd, $sys_dbname_2);
        $this->assertEqual($da->isError(), "Unknown database '".$sys_dbname_2."'");
        $da =& new DataAccess($sys_dbhost, $sys_dbuser, $sys_dbpasswd, $sys_dbname);
        $this->assertFalse($da->isError());
        $this->assertIsA($da->fetch("select *"),'DataAccessResult');
    }
    
    function testQuoteSmart() {
        require(getenv('CODEX_LOCAL_INC'));
        require($GLOBALS['db_config_file']);
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
?>
