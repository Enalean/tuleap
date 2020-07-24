<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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
 */

use PHPUnit\Framework\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Rule_NumericalTest extends TestCase
{

    public function testBiggerThan(): void
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

    public function testBiggerOrEqualThan(): void
    {
        $r = new Rule_GreaterOrEqual(0);

        $this->assertTrue($r->isValid('1'));
        $this->assertTrue($r->isValid('0.1'));
        $this->assertTrue($r->isValid('5'));
        $this->assertTrue($r->isValid('0'));
        $this->assertFalse($r->isValid('-1'));
        $this->assertFalse($r->isValid('toto'));
    }

    public function testLesserThan(): void
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

    public function testLesserOrEqualThan(): void
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

    public function testWhiteList(): void
    {
        $r = new Rule_WhiteList(['-1', '0', '42']);

        $this->assertTrue($r->isValid('-1'));
        $this->assertTrue($r->isValid('0'));
        $this->assertTrue($r->isValid('42'));
        $this->assertFalse($r->isValid('1'));
        $this->assertFalse($r->isValid('100'));
        $this->assertFalse($r->isValid('toto'));
    }
}
