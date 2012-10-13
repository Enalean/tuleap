<?php
/**
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2007.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once('common/valid/Rule.class.php');

class Rule_IntTest extends UnitTestCase {

    function UnitTestCase($name = 'Rule_Int test') {
        $this->UnitTestCase($name);
    }

    function testIsInteger() {
        $r = new Rule_Int();

        $input = '123';
        $this->assertTrue($r->isValid($input));

        $input = '+123';
        $this->assertTrue($r->isValid($input));

        $input = '-123';
        $this->assertTrue($r->isValid($input));

        $input = '+0';
        $this->assertTrue($r->isValid($input));

        $input = '-0';
        $this->assertTrue($r->isValid($input));

        $input = '0';
        $this->assertTrue($r->isValid($input));

    }

    function testFloatingPoint() {
        $r = new Rule_Int();

        $input = '123.3';
        $this->assertFalse($r->isValid($input));
        $input = '123,3';
        $this->assertFalse($r->isValid($input));
    }

    function testStrings() {
        $r = new Rule_Int();

        $input = '123a';
        $this->assertFalse($r->isValid($input));
        $input = '1-23';
        $this->assertFalse($r->isValid($input));
        $input = '123-';
        $this->assertFalse($r->isValid($input));
        $input = 'a123';
        $this->assertFalse($r->isValid($input));
        $input = '123+';
        $this->assertFalse($r->isValid($input));
        $input = 'abc';
        $this->assertFalse($r->isValid($input));
    }

    function testHexadecimal() {
        $r = new Rule_Int();

        $input = '0x12A';
        $this->assertFalse($r->isValid($input));
        $input = '0X12A';
        $this->assertFalse($r->isValid($input));
        $input = '+0x12A';
        $this->assertFalse($r->isValid($input));
        $input = '+0X12A';
        $this->assertFalse($r->isValid($input));
        $input = '-0x12A';
        $this->assertFalse($r->isValid($input));
        $input = '-0X12A';
        $this->assertFalse($r->isValid($input));
        $input = '0x12Y';
        $this->assertFalse($r->isValid($input));
        // start with a '0' (letter) not a zero (figure)
        $input = '0x12A';
        $this->assertFalse($r->isValid($input));
    }

    function testOctal() {
        $r = new Rule_Int();

        $input = '0123';
        $this->assertFalse($r->isValid($input));
        $input = '+0123';
        $this->assertFalse($r->isValid($input));
        $input = '-0123';
        $this->assertFalse($r->isValid($input));
    }

    function testIsBigInt() {
        $r = new Rule_Int();

        // 2^31-1
        $this->assertTrue($r->isValid('2147483647'));
        // -2^31
        $this->assertTrue($r->isValid('-2147483648'));

        if (PHP_INT_SIZE == 4) {
            // 32 bits version
            $this->assertFalse($r->isValid('2147483648'));
            $this->assertFalse($r->isValid('-2147483649'));
        } else {
            // 64 bits version
            $this->assertTrue($r->isValid('2147483648'));
            $this->assertTrue($r->isValid('-2147483649'));
        }
    }

}
?>
