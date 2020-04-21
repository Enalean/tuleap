<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

class Codendi_HTTPPurifierTest extends \PHPUnit\Framework\TestCase // phpcs:ignore
{
    public function testPurify()
    {
        $p = Codendi_HTTPPurifier::instance();
        $this->assertEquals('a', $p->purify("a"));
        $this->assertEquals('a', $p->purify("a\n"));
        $this->assertEquals('a', $p->purify("a\nb"));
        $this->assertEquals('a', $p->purify("a\r"));
        $this->assertEquals('a', $p->purify("a\rb"));
        $this->assertEquals('a', $p->purify("a\r\nb"));
        $this->assertEquals('a', $p->purify("a\0b"));
        $this->assertEquals('', $p->purify("\rabc"));
        $this->assertEquals('', $p->purify("\nabc"));
        $this->assertEquals('', $p->purify("\r\nabc"));
        $this->assertEquals('', $p->purify("\0abc"));
    }
}
