<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2011. All rights reserved
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
use Tuleap\GlobalLanguageMock;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Rule_ProjectFullNameTest extends TestCase
{
    use GlobalLanguageMock;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['Language']->shouldReceive('getText')->with('rule_group_name', 'error_only_spaces')->andReturns('error_only_spaces');
        $GLOBALS['Language']->shouldReceive('getText')->with('include_account', 'name_too_short')->andReturns('name_too_short');
        $GLOBALS['Language']->shouldReceive('getText')->with('include_account', 'name_too_long', 40)->andReturns('name_too_long');
    }

    public function testIsValid(): void
    {
        $rule = new Rule_ProjectFullName();
        $this->assertTrue($rule->isValid("prj"));
        $this->assertNull($rule->getErrorMessage());
        $this->assertTrue($rule->isValid("       project name long by spaces       "));
        $this->assertNull($rule->getErrorMessage());

        $this->assertFalse($rule->isValid(""));
        $this->assertEquals('name_too_short', $rule->getErrorMessage());
        $this->assertFalse($rule->isValid(" "));
        $this->assertEquals('name_too_short', $rule->getErrorMessage());
        $this->assertFalse($rule->isValid("   "));
        $this->assertEquals('name_too_short', $rule->getErrorMessage());
        $this->assertFalse($rule->isValid("p"));
        $this->assertEquals('name_too_short', $rule->getErrorMessage());
        $this->assertFalse($rule->isValid("p   "));
        $this->assertEquals('name_too_short', $rule->getErrorMessage());
        $this->assertFalse($rule->isValid("pr"));
        $this->assertEquals('name_too_short', $rule->getErrorMessage());

        $this->assertFalse($rule->isValid("This a very very long project name longer than 40 characters :)"));
        $this->assertEquals('name_too_long', $rule->getErrorMessage());
    }
}
