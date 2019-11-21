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

class Valid_Widget_OwnerTest extends TestCase
{

    public function testOk(): void
    {
        $v = new Valid_Widget_Owner();

        $this->assertTrue($v->validate("u102"));
        $this->assertTrue($v->validate("g1023"));
        $this->assertTrue($v->validate("u1"));
        $this->assertTrue($v->validate("g1"));
    }

    public function testSpecialChars(): void
    {
        $v = new Valid_Widget_Owner();

        $this->assertFalse($v->validate("u102\n"));
        $this->assertFalse($v->validate("\nu102"));
        $this->assertFalse($v->validate("u102\nu102"));

        $this->assertFalse($v->validate("u102\0"));
        $this->assertFalse($v->validate("\0u102"));
        $this->assertFalse($v->validate("u102\0u102"));
    }

    public function testSqlInjections(): void
    {
        $v = new Valid_Widget_Owner();

        $this->assertFalse($v->validate("u102--"));
        $this->assertFalse($v->validate("--u102"));
        $this->assertFalse($v->validate("--"));
    }

    public function testHTMLInjections(): void
    {
        $v = new Valid_Widget_Owner();

        $this->assertFalse($v->validate("<script>alert(1);</script>"));
        $this->assertFalse($v->validate("\"<script>alert(1);</script>"));
        $this->assertFalse($v->validate("\"><script>alert(1);</script>"));
        $this->assertFalse($v->validate("</textarea><script>alert(1);</script>"));
    }
}
