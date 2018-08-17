<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Tests the class DataAccess
 */
class DataAccessTest extends TuleapTestCase {

    public function skip()
    {
        $this->skipIf(PHP_VERSION_ID > 70000);
    }

    function testConnection() {
        
        $sys_dbhost   = 'host';
        $sys_dbuser   = 'user';
        $sys_dbpasswd = 'pass';
        $sys_dbname   = 'db';
        $this->expectException('DataAccessException');
        $data_access_credentials = new DataAccessCredentials($sys_dbhost, $sys_dbuser, $sys_dbpasswd, $sys_dbname);
        partial_mock('DataAccess', array('connect', 'getErrorMessage'), array($data_access_credentials));
    }
    
    function testQuoteSmart() {
        $da = partial_mock('DataAccess', array('connect'));
        $this->assertIdentical("'123'", $da->quoteSmart("123"));
        $this->assertIdentical("'12.3'", $da->quoteSmart("12.3"));
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

    function testQuoteSmartImplode() {
        $da = partial_mock('DataAccess', array('connect'));
        $this->assertIdentical("'123'", $da->quoteSmartImplode('',array("123")), "Array with one element");
        $this->assertIdentical("'123''456'", $da->quoteSmartImplode('',array("123","456")), "Glue is empty");
        $this->assertIdentical("'123' '456'", $da->quoteSmartImplode(' ',array("123","456")), "Glue is empty");
        $this->assertIdentical("'val1'", $da->quoteSmartImplode(' ',array("val1")), "Array with one string");
        $this->assertIdentical("'val1' OR 'val2'", $da->quoteSmartImplode(' OR ',array("val1","val2")), "Array with two strings");
        $this->assertIdentical("'val1' OR 'val2' OR '34'", $da->quoteSmartImplode(' OR ',array("val1","val2",34)), "Array with three elements");
        $this->assertIdentical("'val1''val2'", $da->quoteSmartImplode('',array("val1","val2")), "Array with two strings and no glue"); // Is this what we really expect??
        $this->assertIdentical("'val\\'1' OR 'val2'", $da->quoteSmartImplode(' OR ',array("val'1","val2")), "Array with two strings");
        $this->assertIdentical("'val1'''val2'", $da->quoteSmartImplode("'",array("val1","val2")), "Glue is not escaped");// Is this what we really expect??
   }

   function testEscapeIntImplode() {
       $da = partial_mock('DataAccess', array('connect'));
       $this->assertIdentical('12,34,+5,-6,0', $da->escapeIntImplode(array(12, '34', '+5', '-6', 'crap')));
   }

   public function itQuotesLikeValueSurround()
   {
       $data_access             = partial_mock('DataAccess', array('connect'));
       $string_to_escape        = '_%\\';
       $expected_escaped_string = '\_\%\\\\';
       $this->assertEqual($data_access->escapeLikeValue($string_to_escape), $expected_escaped_string);
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
        $this->assertEqual(DataAccess::escapeInt('', CODENDI_DB_NOT_NULL), '0');
        $this->assertEqual(DataAccess::escapeInt('', CODENDI_DB_NULL), 'NULL');

        $this->assertEqual(DataAccess::escapeInt('0', CODENDI_DB_NULL), '0');
        $this->assertEqual(DataAccess::escapeInt(null, CODENDI_DB_NULL), '0');
        $this->assertEqual(DataAccess::escapeInt('123', CODENDI_DB_NULL), '123');
        $this->assertEqual(DataAccess::escapeInt('abc', CODENDI_DB_NULL), '0');
    }
}
