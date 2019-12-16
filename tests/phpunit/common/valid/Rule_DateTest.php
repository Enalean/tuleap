<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

use PHPUnit\Framework\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Rule_DateTest extends TestCase
{

    public function testBadDate(): void
    {
        $r = new Rule_Date();
        $this->assertFalse($r->isValid('2007-13-5'));
        $this->assertFalse($r->isValid('2007-12-32'));
    }

    public function testBadFormat(): void
    {
        $r = new Rule_Date();
        $this->assertFalse($r->isValid('2007-12'));
        $this->assertFalse($r->isValid('toto'));
        $this->assertTrue($r->isValid('2007-01-01'));
        $this->assertTrue($r->isValid('2007-01-1'));
        $this->assertTrue($r->isValid('2007-1-01'));
    }

    public function testGoodDate(): void
    {
        $r = new Rule_Date();
        $this->assertTrue($r->isValid('2007-11-30'));
        $this->assertTrue($r->isValid('2007-12-31'));
        $this->assertTrue($r->isValid('2007-1-1'));
        $this->assertTrue($r->isValid('200-1-5'));
    }

    public function testLeapYear(): void
    {
        $r = new Rule_Date();
        $this->assertFalse($r->isValid('2001-2-29'));
        $this->assertTrue($r->isValid('2004-2-29'));
    }
}
