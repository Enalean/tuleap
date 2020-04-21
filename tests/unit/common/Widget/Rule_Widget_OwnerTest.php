<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2008.
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

class Rule_Widget_OwnerTest extends TestCase
{

    public function testOk(): void
    {
        $r = new Rule_Widget_Owner();
        $this->assertTrue($r->isValid("u102"));
        $this->assertTrue($r->isValid("g1023"));
        $this->assertTrue($r->isValid("u1"));
        $this->assertTrue($r->isValid("g1"));
    }

    public function testBadFormat(): void
    {
        $r = new Rule_Widget_Owner();
        $this->assertFalse($r->isValid("uw102"));
        $this->assertFalse($r->isValid("10asd"));
        $this->assertFalse($r->isValid("?"));
        $this->assertFalse($r->isValid("a"));
        $this->assertFalse($r->isValid("1"));
        $this->assertFalse($r->isValid("\n"));
        $this->assertFalse($r->isValid("\0"));
    }
}
