<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
 * Originally written by Nicolas TERRAY, 2008.
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

/**
 * Tests the class Codendi_HTTPPurifier
 */
class Codendi_HTTPPurifierTest extends TuleapTestCase
{
    function testPurify()
    {
        $p = Codendi_HTTPPurifier::instance();
        $this->assertEqual('a', $p->purify("a"));
        $this->assertEqual('a', $p->purify("a\n"));
        $this->assertEqual('a', $p->purify("a\nb"));
        $this->assertEqual('a', $p->purify("a\r"));
        $this->assertEqual('a', $p->purify("a\rb"));
        $this->assertEqual('a', $p->purify("a\r\nb"));
        $this->assertEqual('a', $p->purify("a\0b"));
        $this->assertEqual('', $p->purify("\rabc"));
        $this->assertEqual('', $p->purify("\nabc"));
        $this->assertEqual('', $p->purify("\r\nabc"));
        $this->assertEqual('', $p->purify("\0abc"));
    }
}
