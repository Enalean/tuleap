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

class Valid_DateTest extends UnitTestCase {

    function UnitTestCase($name = 'Valid_Date test') {
        $this->UnitTestCase($name);
    }

    function testBadDate() {
        $v = new Valid_Date();
        $this->assertFalse($v->isValid('2007-13-5'));
        $this->assertFalse($v->isValid('2007-12-32'));
    }

    function testBadFormat() {
        $v = new Valid_Date();
        $this->assertFalse($v->isValid('2007-12'));
        $this->assertFalse($v->isValid('toto'));
        $this->assertFalse($v->isValid('2007-01-01'));
        $this->assertFalse($v->isValid('2007-01-1'));
        $this->assertFalse($v->isValid('2007-1-01'));
    }

    function testGoodDate() {
        $v = new Valid_Date();
        $this->assertTrue($v->isValid('2007-11-30'));
        $this->assertTrue($v->isValid('2007-12-31'));
        $this->assertTrue($v->isValid('2007-1-1'));
        $this->assertTrue($v->isValid('200-1-5'));
    }

    function testLeapYear() {
        $v = new Valid_Date();
        $this->assertFalse($v->isValid('2001-2-29'));
        $this->assertTrue($v->isValid('2004-2-29'));
    }

}
?>
