<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2007.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

class Rule_NumericalTest extends TuleapTestCase
{

    function UnitTestCase($name = 'Rule_Numerical test')
    {
        $this->UnitTestCase($name);
    }

    function testBiggerThan()
    {
        $r = new Rule_GreaterThan(-1);

        $this->assertTrue($r->isValid('0.9'));
        $this->assertTrue($r->isValid('1'));
        $this->assertTrue($r->isValid('5'));
        $this->assertFalse($r->isValid('-1'));
        $this->assertFalse($r->isValid('-1.1'));
        $this->assertFalse($r->isValid('-9'));
        $this->assertFalse($r->isValid('toto'));
    }

    function testBiggerOrEqualThan()
    {
        $r = new Rule_GreaterOrEqual(0);

        $this->assertTrue($r->isValid('1'));
        $this->assertTrue($r->isValid('0.1'));
        $this->assertTrue($r->isValid('5'));
        $this->assertTrue($r->isValid('0'));
        $this->assertFalse($r->isValid('-1'));
        $this->assertFalse($r->isValid('toto'));
    }

    function testLesserThan()
    {
        $r = new Rule_LessThan(10);

        $this->assertTrue($r->isValid('1'));
        $this->assertTrue($r->isValid('-5'));
        $this->assertTrue($r->isValid('0'));
        $this->assertTrue($r->isValid('9.99'));
        $this->assertFalse($r->isValid('10.01'));
        $this->assertFalse($r->isValid('10'));
        $this->assertFalse($r->isValid('11'));
        $this->assertFalse($r->isValid('20'));
        $this->assertFalse($r->isValid('toto'));
    }

    function testLesserOrEqualThan()
    {
        $r = new Rule_lessOrEqual(10);

        $this->assertTrue($r->isValid('1'));
        $this->assertTrue($r->isValid('-5'));
        $this->assertTrue($r->isValid('0'));
        $this->assertTrue($r->isValid('10'));
        $this->assertFalse($r->isValid('10.01'));
        $this->assertFalse($r->isValid('20'));
        $this->assertFalse($r->isValid('toto'));
    }

    function testWhiteList()
    {
        $r = new Rule_WhiteList(array('-1', '0', '42'));

        $this->assertTrue($r->isValid('-1'));
        $this->assertTrue($r->isValid('0'));
        $this->assertTrue($r->isValid('42'));
        $this->assertFalse($r->isValid('1'));
        $this->assertFalse($r->isValid('100'));
        $this->assertFalse($r->isValid('toto'));
    }

    /*function testRange() {
        $r = new Valid_Numerical();
        $r->biggerThan(-1);
        $r->lesserThan(3);

        $this->assertFalse($r->isValid('-1'));
        $this->assertTrue($r->isValid('0'));
        $this->assertTrue($r->isValid('1'));
        $this->assertTrue($r->isValid('2'));
        $this->assertFalse($r->isValid('3'));
    }

    function testMostRestrictive() {
        $r = new Valid_Numerical();
        $r->allowedValues(array('-1', '5', '42'));
        $r->biggerThan(-1);
        $r->lesserThan(6);

        $this->assertTrue($r->isValid('5'));
        $this->assertFalse($r->isValid('-1'));
        $this->assertFalse($r->isValid('42'));
    }

    function testMinStrictAndMinEqual() {
        $r = new Valid_Numerical();
        $r->biggerOrEqual(0);
        $r->biggerThan(0);

        $this->assertTrue($r->isValid('1'));
        $this->assertTrue($r->isValid('5'));
        $this->assertFalse($r->isValid('0'));
        $this->assertFalse($r->isValid('-9'));
    }

    function testMaxStrictAndMaxEqualThan() {
        $r = new Valid_Numerical();
        $r->lesserThan(10);
        $r->lesserOrEqual(10);

        $this->assertTrue($r->isValid('1'));
        $this->assertTrue($r->isValid('-5'));
        $this->assertTrue($r->isValid('0'));
        $this->assertFalse($r->isValid('10'));
        $this->assertFalse($r->isValid('11'));
        $this->assertFalse($r->isValid('20'));
    }*/
}
