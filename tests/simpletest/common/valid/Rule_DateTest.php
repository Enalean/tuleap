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


class Rule_DateTest extends TuleapTestCase
{

    function UnitTestCase($name = 'Rule_Date test')
    {
        $this->UnitTestCase($name);
    }

    function testBadDate()
    {
        $r = new Rule_Date();
        $this->assertFalse($r->isValid('2007-13-5'));
        $this->assertFalse($r->isValid('2007-12-32'));
    }

    function testBadFormat()
    {
        $r = new Rule_Date();
        $this->assertFalse($r->isValid('2007-12'));
        $this->assertFalse($r->isValid('toto'));
        $this->assertTrue($r->isValid('2007-01-01'));
        $this->assertTrue($r->isValid('2007-01-1'));
        $this->assertTrue($r->isValid('2007-1-01'));
    }

    function testGoodDate()
    {
        $r = new Rule_Date();
        $this->assertTrue($r->isValid('2007-11-30'));
        $this->assertTrue($r->isValid('2007-12-31'));
        $this->assertTrue($r->isValid('2007-1-1'));
        $this->assertTrue($r->isValid('200-1-5'));
    }

    function testLeapYear()
    {
        $r = new Rule_Date();
        $this->assertFalse($r->isValid('2001-2-29'));
        $this->assertTrue($r->isValid('2004-2-29'));
    }
}
