<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 */

class Rule_RealNameTest extends TuleapTestCase
{
    private $rule;

    public function setUp()
    {
        parent::setUp();
        $this->rule = new Rule_RealName();
    }

    public function itForbidsCRLFChar()
    {
        $this->assertFalse($this->rule->isValid("toto\ntata"));
        $this->assertFalse($this->rule->isValid("toto
tata"));
        $this->assertFalse($this->rule->isValid("\ntata"));
    }

    public function itForbidsBackslashN()
    {
        $this->assertFalse($this->rule->isValid('toto\ntata'));
    }

    public function itForbidsBellChar()
    {
        $this->assertFalse($this->rule->isValid("toto\atard"));
        $this->assertFalse($this->rule->isValid('toto\atard'));
    }

    public function itForbidsTabChar()
    {
        $this->assertFalse($this->rule->isValid("tot\tata"));
        $this->assertFalse($this->rule->isValid('tot\tata'));
    }

    public function itAllowsValidNameWithSpace()
    {
        $this->assertTrue($this->rule->isValid('John Doe'));
    }

    public function itAllowsValidNameWithUTF8Chars()
    {
        $this->assertTrue($this->rule->isValid('你hǎo 好'));
        $this->assertTrue($this->rule->isValid('いろはにほへとちりぬるを'));
    }
}
