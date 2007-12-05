<?php
/**
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2007.
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once('common/validator/Validator.class.php');

class Valid_IntTest extends UnitTestCase {

    function UnitTestCase($name = 'Valid_Int test') {
        $this->UnitTestCase($name);
    }

    function testIsInteger() {
        $v = new Valid_Int();

        $input = '123';
        $this->assertTrue($v->isValid($input));

        $input = '+123';
        $this->assertTrue($v->isValid($input));

        $input = '-123';
        $this->assertTrue($v->isValid($input));

        $input = '+0';
        $this->assertTrue($v->isValid($input));

        $input = '-0';
        $this->assertTrue($v->isValid($input));

        $input = '0';
        $this->assertTrue($v->isValid($input));

    }

    function testFloatingPoint() {
        $v = new Valid_Int();

        $input = '123.3';
        $this->assertFalse($v->isValid($input));
        $input = '123,3';
        $this->assertFalse($v->isValid($input));
    }

    function testStrings() {
        $v = new Valid_Int();

        $input = '123a';
        $this->assertFalse($v->isValid($input));
        $input = '1-23';
        $this->assertFalse($v->isValid($input));
        $input = '123-';
        $this->assertFalse($v->isValid($input));
        $input = 'a123';
        $this->assertFalse($v->isValid($input));
        $input = '123+';
        $this->assertFalse($v->isValid($input));
        $input = 'abc';
        $this->assertFalse($v->isValid($input));
    }

    function testHexadecimal() {
        $v = new Valid_Int();

        $input = '0x12A';
        $this->assertFalse($v->isValid($input));
        $input = '0X12A';
        $this->assertFalse($v->isValid($input));
        $input = '+0x12A';
        $this->assertFalse($v->isValid($input));
        $input = '+0X12A';
        $this->assertFalse($v->isValid($input));
        $input = '-0x12A';
        $this->assertFalse($v->isValid($input));
        $input = '-0X12A';
        $this->assertFalse($v->isValid($input));
        $input = '0x12Y';
        $this->assertFalse($v->isValid($input));
        // start with a '0' (letter) not a zero (figure)
        $input = '0x12A';
        $this->assertFalse($v->isValid($input));
    }

    function testOctal() {
        $v = new Valid_Int();

        $input = '0123';
        $this->assertFalse($v->isValid($input));
        $input = '+0123';
        $this->assertFalse($v->isValid($input));
        $input = '-0123';
        $this->assertFalse($v->isValid($input));
    }

    function testIsBigInt() {
        $v = new Valid_Int();

        // 2^31-1
        $this->assertTrue($v->isValid('2147483647'));
        $this->assertFalse($v->isValid('2147483648'));

        // -2^31
        $this->assertTrue($v->isValid('-2147483648'));
        $this->assertFalse($v->isValid('-2147483649'));
    }

}
?>
