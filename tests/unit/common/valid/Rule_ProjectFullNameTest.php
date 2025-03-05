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

use Tuleap\GlobalLanguageMock;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class Rule_ProjectFullNameTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    public function testIsValid(): void
    {
        $GLOBALS['Language']->method('getText')->willReturnCallback(
            static function (string $page_name, string $category, $args): string {
                if ($page_name === 'include_account' && $category === 'name_too_short') {
                    return 'name_too_short';
                }

                throw new LogicException(sprintf('Unexpected call to getText(%s, %s, %d)', $page_name, $category, $args));
            }
        );

        $rule = new Rule_ProjectFullName();
        $this->assertTrue($rule->isValid('prj'));
        $this->assertNull($rule->getErrorMessage());
        $this->assertTrue($rule->isValid('       project name long by spaces       '));
        $this->assertNull($rule->getErrorMessage());

        $this->assertFalse($rule->isValid(''));
        $this->assertEquals('name_too_short', $rule->getErrorMessage());
        $this->assertFalse($rule->isValid(' '));
        $this->assertEquals('name_too_short', $rule->getErrorMessage());
        $this->assertFalse($rule->isValid('   '));
        $this->assertEquals('name_too_short', $rule->getErrorMessage());
        $this->assertFalse($rule->isValid('p'));
        $this->assertEquals('name_too_short', $rule->getErrorMessage());
        $this->assertFalse($rule->isValid('p   '));
        $this->assertEquals('name_too_short', $rule->getErrorMessage());
        $this->assertFalse($rule->isValid('pr'));
        $this->assertEquals('name_too_short', $rule->getErrorMessage());

        $this->assertTrue($rule->isValid('It accepts long string with accents éééé'));

        $this->assertFalse($rule->isValid('This a very very long project name longer than 40 characters :)'));
        $this->assertEquals('Name is too long. It must be less than 40 characters.', $rule->getErrorMessage());
    }
}
