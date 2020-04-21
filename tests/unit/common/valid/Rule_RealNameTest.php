<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use PHPUnit\Framework\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Rule_RealNameTest extends TestCase
{
    private $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new Rule_RealName();
    }

    public function testItForbidsCRLFChar(): void
    {
        $this->assertFalse($this->rule->isValid("toto\ntata"));
        $this->assertFalse($this->rule->isValid("toto
tata"));
        $this->assertFalse($this->rule->isValid("\ntata"));
    }

    public function testItForbidsBackslashN(): void
    {
        $this->assertFalse($this->rule->isValid('toto\ntata'));
    }

    public function testItForbidsBellChar(): void
    {
        $this->assertFalse($this->rule->isValid("toto\atard"));
        $this->assertFalse($this->rule->isValid('toto\atard'));
    }

    public function testItForbidsTabChar(): void
    {
        $this->assertFalse($this->rule->isValid("tot\tata"));
        $this->assertFalse($this->rule->isValid('tot\tata'));
    }

    public function testItAllowsValidNameWithSpace(): void
    {
        $this->assertTrue($this->rule->isValid('John Doe'));
    }

    public function testItAllowsValidNameWithUTF8Chars(): void
    {
        $this->assertTrue($this->rule->isValid('你hǎo 好'));
        $this->assertTrue($this->rule->isValid('いろはにほへとちりぬるを'));
    }
}
