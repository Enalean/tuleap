<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2008.
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


class Rule_StringTest extends TuleapTestCase
{

    function UnitTestCase($name = 'Rule_String test')
    {
        $this->UnitTestCase($name);
    }

    function testCr()
    {
        $r = new Rule_NoCr();
        $this->assertTrue($r->isValid('1'));
        $this->assertTrue($r->isValid('abcd'));
        $this->assertTrue($r->isValid('abcd efg'));

        $this->assertIdentical("\n", chr(10));

        // Unix
        $this->assertFalse($r->isValid("abcd\nfg"));
        $this->assertFalse($r->isValid("\nabcdfg"));
        $this->assertFalse($r->isValid("abcdfg\n"));
        // Windows
        $this->assertFalse($r->isValid("abcd\r\nfg"));
        $this->assertFalse($r->isValid("\r\nabcdfg"));
        $this->assertFalse($r->isValid("abcdfg\r\n"));
        // Mac
        $this->assertFalse($r->isValid("abcd\rfg"));
        $this->assertFalse($r->isValid("\rabcdfg"));
        $this->assertFalse($r->isValid("abcdfg\r"));

        $array = array("abcdfg");
        $this->assertFalse($r->isValid($array));
    }

    function testNull()
    {
        $r = new Rule_NoCr();
        $this->assertIdentical("\0", chr(0));
        $this->assertFalse($r->isValid("abcd\0fg"));
        $this->assertFalse($r->isValid("\0abcdfg"));
        $this->assertFalse($r->isValid("abcdfg\0"));
    }
}
