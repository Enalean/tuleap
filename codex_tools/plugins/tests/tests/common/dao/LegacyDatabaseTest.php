<?php

require_once('database.php');

class LegacyDatabaseTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function CodexDataAccessTest($name = 'LegacyDatabase test') {
        $this->UnitTestCase($name);
    }


    function testIsInteger() {
        $input = '123';
        $this->assertEqual(db_escape_int($input), 123);

        $input = '+123';
        $this->assertEqual(db_escape_int($input), 123);

        $input = '-123';
        $this->assertEqual(db_escape_int($input), -123);

        $input = '+0';
        $this->assertEqual(db_escape_int($input), 0);

        $input = '-0';
        $this->assertEqual(db_escape_int($input), 0);

        $input = '0';
        $this->assertEqual(db_escape_int($input), 0);

    }

    function testFloatingPoint() {
        $input = '123.3';
        $this->assertEqual(db_escape_int($input), 0);
        $input = '123,3';
        $this->assertEqual(db_escape_int($input), 0);
    }

    function testStrings() {
        $input = '123a';
        $this->assertEqual(db_escape_int($input), 0);
        $input = '1-23';
        $this->assertEqual(db_escape_int($input), 0);
        $input = '123-';
        $this->assertEqual(db_escape_int($input), 0);
        $input = 'a123';
        $this->assertEqual(db_escape_int($input), 0);
        $input = '123+';
        $this->assertEqual(db_escape_int($input), 0);
        $input = 'abc';
        $this->assertEqual(db_escape_int($input), 0);
    }

    function testHexadecimal() {
        $input = '0x12A';
        $this->assertEqual(db_escape_int($input), 0);
        $input = '0X12A';
        $this->assertEqual(db_escape_int($input), 0);
        $input = '+0x12A';
        $this->assertEqual(db_escape_int($input), 0);
        $input = '+0X12A';
        $this->assertEqual(db_escape_int($input), 0);
        $input = '-0x12A';
        $this->assertEqual(db_escape_int($input), 0);
        $input = '-0X12A';
        $this->assertEqual(db_escape_int($input), 0);
        $input = '0x12Y';
        $this->assertEqual(db_escape_int($input), 0);
        // start with a '0' (letter) not a zero (figure)
        $input = '0x12A';
        $this->assertEqual(db_escape_int($input), 0);
    }

    function testOctal() {
        $input = '0123';
        $this->assertEqual(db_escape_int($input), 0);
        $input = '+0123';
        $this->assertEqual(db_escape_int($input), 0);
        $input = '-0123';
        $this->assertEqual(db_escape_int($input), 0);
    }

    function testIsBigInt() {
        $input = '2147483649';
        $this->assertEqual(db_escape_int($input), 2147483649);

        $input = '-214748364790';
        $this->assertEqual(db_escape_int($input), -214748364790);
    }

    /*function testVeryBigInput() {
        $f = file_get_contents('hamlet_1.txt');
        $v = db_escape_int($f);
        $this->assertNoErrors();
        $this->assertEqual(db_escape_int($f), $v);
    }*/
}

?>
