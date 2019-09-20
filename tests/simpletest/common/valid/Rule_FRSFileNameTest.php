<?php
/**
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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

require_once __DIR__ . '/../../../../src/www/include/utils.php';

class Rule_FRSFileNameTest extends TuleapTestCase
{

    function testNameValid()
    {
        $r = new Rule_FRSFileName();
        $this->assertTrue($r->isValid('toto.txt'));

        $this->assertTrue($r->isValid('toto tutu.txt'));
    }

    protected function _testStringWithChar($c)
    {
        $r = new Rule_FRSFileName();

        // start
        $this->assertFalse($r->isValid($c.'tototutu'), $c." is not allowed");

        // middle
        $this->assertFalse($r->isValid('toto'.$c.'tutu'), $c." is not allowed");

        // end
        $this->assertFalse($r->isValid('tototutu'.$c), $c." is not allowed");
    }

    function testNameContainsInvalidCharacterAnywhere()
    {
        $str = "`!\"$%^,&*();=|{}<>?/";
        for ($i = 0; $i < strlen($str); $i++) {
            $this->_testStringWithChar($str[$i]);
        }
    }

    function testNameContainsSpecialCharAtBeginning()
    {
        $r = new Rule_FRSFileName();
        $this->assertTrue($r->isValid('toto@tutu'));

        $this->assertTrue($r->isValid('toto~tutu'));

        $this->assertFalse($r->isValid('@toto'));

        $this->assertFalse($r->isValid('~toto'));
    }

    function testNameContainsDot()
    {
        $r = new Rule_FRSFileName();

        $this->assertFalse($r->isValid('../coin'));

        $this->assertFalse($r->isValid('zata/../toto'));
    }
}
