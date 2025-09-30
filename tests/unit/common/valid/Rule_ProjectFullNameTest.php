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


//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class Rule_ProjectFullNameTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testIsValid(): void
    {
        $rule = new Rule_ProjectFullName();
        $this->assertTrue($rule->isValid('prj'));
        $this->assertEmpty($rule->getErrorMessage());
        $this->assertTrue($rule->isValid('       project name long by spaces       '));
        $this->assertEmpty($rule->getErrorMessage());

        $this->assertFalse($rule->isValid(''));
        $this->assertStringContainsString('Name is too short', $rule->getErrorMessage());
        $this->assertFalse($rule->isValid(' '));
        $this->assertStringContainsString('Name is too short', $rule->getErrorMessage());
        $this->assertFalse($rule->isValid('   '));
        $this->assertStringContainsString('Name is too short', $rule->getErrorMessage());
        $this->assertFalse($rule->isValid('p'));
        $this->assertStringContainsString('Name is too short', $rule->getErrorMessage());
        $this->assertFalse($rule->isValid('p   '));
        $this->assertStringContainsString('Name is too short', $rule->getErrorMessage());
        $this->assertFalse($rule->isValid('pr'));
        $this->assertStringContainsString('Name is too short', $rule->getErrorMessage());

        $this->assertTrue($rule->isValid('It accepts long string with accents éééé'));

        $this->assertFalse($rule->isValid('This a very very long project name longer than 40 characters :)'));
        $this->assertEquals('Name is too long. It must be less than 40 characters.', $rule->getErrorMessage());
    }
}
