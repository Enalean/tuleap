<?php
require_once('common/dao/include/DataAccess.class.php');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
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
        $this->expectException('DataAccessException');
        $this->expectError();
        $da =& new DataAccess($sys_dbhost, $sys_dbuser, $sys_dbpasswd, $sys_dbname);
        
        require(getenv('CODEX_LOCAL_INC'));
        require($GLOBALS['db_config_file']);
        $sys_dbname_2 = 'db that does not exist';
        $da =& new DataAccess($sys_dbhost, $sys_dbuser, $sys_dbpasswd, $sys_dbname_2);
        $this->assertError("Unknown database '".$sys_dbname_2."'");
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
        $this->assertIdentical("'evil\\'s value'", $da->quoteSmart("evil's value"));
        $this->assertIdentical("'\\\\x00'", $da->quoteSmart('\\x00'));
        $this->assertIdentical("'\\\\n'", $da->quoteSmart('\\n'));
        $this->assertIdentical("'\\\\r'", $da->quoteSmart("\\r"));
        $this->assertIdentical("'\\\\'", $da->quoteSmart("\\"));
        $this->assertIdentical("'\\''", $da->quoteSmart("'"));
        $this->assertIdentical("'\\\"'", $da->quoteSmart("\""));
        $this->assertIdentical("'\\\\x1a'", $da->quoteSmart("\\x1a"));
    }


    function testIsInteger() {
        $input = '123';
        $this->assertEqual(DataAccess::escapeInt($input), 123);

        $input = '+123';
        $this->assertEqual(DataAccess::escapeInt($input), 123);

        $input = '-123';
        $this->assertEqual(DataAccess::escapeInt($input), -123);

        $input = '+0';
        $this->assertEqual(DataAccess::escapeInt($input), 0);

        $input = '-0';
        $this->assertEqual(DataAccess::escapeInt($input), 0);

        $input = '0';
        $this->assertEqual(DataAccess::escapeInt($input), 0);

    }

    function testFloatingPoint() {
        $input = '123.3';
        $this->assertEqual(DataAccess::escapeInt($input), 0);
        $input = '123,3';
        $this->assertEqual(DataAccess::escapeInt($input), 0);
    }

    function testStrings() {
        $input = '123a';
        $this->assertEqual(DataAccess::escapeInt($input), 0);
        $input = '1-23';
        $this->assertEqual(DataAccess::escapeInt($input), 0);
        $input = '123-';
        $this->assertEqual(DataAccess::escapeInt($input), 0);
        $input = 'a123';
        $this->assertEqual(DataAccess::escapeInt($input), 0);
        $input = '123+';
        $this->assertEqual(DataAccess::escapeInt($input), 0);
        $input = 'abc';
        $this->assertEqual(DataAccess::escapeInt($input), 0);
    }

    function testHexadecimal() {
        $input = '0x12A';
        $this->assertEqual(DataAccess::escapeInt($input), 0);
        $input = '0X12A';
        $this->assertEqual(DataAccess::escapeInt($input), 0);
        $input = '+0x12A';
        $this->assertEqual(DataAccess::escapeInt($input), 0);
        $input = '+0X12A';
        $this->assertEqual(DataAccess::escapeInt($input), 0);
        $input = '-0x12A';
        $this->assertEqual(DataAccess::escapeInt($input), 0);
        $input = '-0X12A';
        $this->assertEqual(DataAccess::escapeInt($input), 0);
        $input = '0x12Y';
        $this->assertEqual(DataAccess::escapeInt($input), 0);
        // start with a '0' (letter) not a zero (figure)
        $input = '0x12A';
        $this->assertEqual(DataAccess::escapeInt($input), 0);
    }

    function testOctal() {
        $input = '0123';
        $this->assertEqual(DataAccess::escapeInt($input), 0);
        $input = '+0123';
        $this->assertEqual(DataAccess::escapeInt($input), 0);
        $input = '-0123';
        $this->assertEqual(DataAccess::escapeInt($input), 0);
    }

    function testIsBigInt() {
        $input = '2147483649';
        $this->assertEqual(DataAccess::escapeInt($input), 2147483649);

        $input = '-214748364790';
        $this->assertEqual(DataAccess::escapeInt($input), -214748364790);
    }

    function testNull() {
        $this->assertEqual(DataAccess::escapeInt(''), '0');
        $this->assertEqual(DataAccess::escapeInt('', CODEX_DB_NOT_NULL), '0');
        $this->assertEqual(DataAccess::escapeInt('', CODEX_DB_NULL), 'NULL');

        $this->assertEqual(DataAccess::escapeInt('0', CODEX_DB_NULL), '0');
        $this->assertEqual(DataAccess::escapeInt(null, CODEX_DB_NULL), '0');
        $this->assertEqual(DataAccess::escapeInt('123', CODEX_DB_NULL), '123');
        $this->assertEqual(DataAccess::escapeInt('abc', CODEX_DB_NULL), '0');
    }
}
?>
