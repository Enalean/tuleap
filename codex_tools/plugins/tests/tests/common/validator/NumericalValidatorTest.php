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

class NumericalValidatorTest extends UnitTestCase {

    function UnitTestCase($name = 'NumericalValidator test') {
        $this->UnitTestCase($name);
    }

    function testBiggerThan() {
        $v = new NumericalValidator();
        $v->biggerThan(0);

        $this->assertTrue($v->isValid('1'));
        $this->assertTrue($v->isValid('5'));
        $this->assertFalse($v->isValid('0'));
        $this->assertFalse($v->isValid('-9'));
    }

    function testBiggerOrEqualThan() {
        $v = new NumericalValidator();
        $v->biggerOrEqualThan(0);

        $this->assertTrue($v->isValid('1'));
        $this->assertTrue($v->isValid('5'));
        $this->assertTrue($v->isValid('0'));
        $this->assertFalse($v->isValid('-1'));
    }

    function testLesserThan() {
        $v = new NumericalValidator();
        $v->lesserThan(10);

        $this->assertTrue($v->isValid('1'));
        $this->assertTrue($v->isValid('-5'));
        $this->assertTrue($v->isValid('0'));
        $this->assertFalse($v->isValid('10'));
        $this->assertFalse($v->isValid('11'));
        $this->assertFalse($v->isValid('20'));
    }

    function testLesserOrEqualThan() {
        $v = new NumericalValidator();
        $v->lesserOrEqualThan(10);

        $this->assertTrue($v->isValid('1'));
        $this->assertTrue($v->isValid('-5'));
        $this->assertTrue($v->isValid('0'));
        $this->assertTrue($v->isValid('10'));
        $this->assertFalse($v->isValid('11'));
        $this->assertFalse($v->isValid('20'));
    }

    function testAllowedValues() {
        $v = new NumericalValidator();
        $v->allowedValues(array('-1', '5', '42'));

        $this->assertTrue($v->isValid('-1'));
        $this->assertTrue($v->isValid('5'));
        $this->assertTrue($v->isValid('42'));
        $this->assertFalse($v->isValid('0'));
        $this->assertFalse($v->isValid('1'));
        $this->assertFalse($v->isValid('100'));
    }

    function testRange() {
        $v = new NumericalValidator();
        $v->biggerThan(-1);
        $v->lesserThan(3);

        $this->assertFalse($v->isValid('-1'));
        $this->assertTrue($v->isValid('0'));
        $this->assertTrue($v->isValid('1'));
        $this->assertTrue($v->isValid('2'));
        $this->assertFalse($v->isValid('3'));
    }

    function testMostRestrictive() {
        $v = new NumericalValidator();
        $v->allowedValues(array('-1', '5', '42'));
        $v->biggerThan(-1);
        $v->lesserThan(6);

        $this->assertTrue($v->isValid('5'));
        $this->assertFalse($v->isValid('-1'));
        $this->assertFalse($v->isValid('42'));
    }

    function testMinStrictAndMinEqual() {
        $v = new NumericalValidator();
        $v->biggerOrEqualThan(0);
        $v->biggerThan(0);

        $this->assertTrue($v->isValid('1'));
        $this->assertTrue($v->isValid('5'));
        $this->assertFalse($v->isValid('0'));
        $this->assertFalse($v->isValid('-9'));
    }

    function testMaxStrictAndMaxEqualThan() {
        $v = new NumericalValidator();
        $v->lesserThan(10);
        $v->lesserOrEqualThan(10);

        $this->assertTrue($v->isValid('1'));
        $this->assertTrue($v->isValid('-5'));
        $this->assertTrue($v->isValid('0'));
        $this->assertFalse($v->isValid('10'));
        $this->assertFalse($v->isValid('11'));
        $this->assertFalse($v->isValid('20'));
    }

}
?>
