<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\CreateTestEnv;

use Tuleap\GlobalLanguageMock;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CreateTestProjectTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    #[\PHPUnit\Framework\Attributes\DataProvider('userNameProvider')]
    public function testUnixNameIsValid(string $username, string $expected_result): void
    {
        $create = new CreateTestProject(
            $username,
            '/archive/path',
            $this->createMock(\Rule_ProjectName::class),
            $this->createMock(\Rule_ProjectFullName::class)
        );
        $this->assertEquals($expected_result, $create->generateProjectUnixName());
    }

    public static function userNameProvider(): array
    {
        return [
            ['joperesr', 'test-for-joperesr' ],
            ['jope_resr', 'test-for-jope-resr' ],
            ['jope.resr', 'test-for-jope-resr' ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('fullNameProvider')]
    public function testFullNameIsValid(string $username, string $expected_result): void
    {
        $create = new CreateTestProject(
            $username,
            '/archive/path',
            $this->createMock(\Rule_ProjectName::class),
            $this->createMock(\Rule_ProjectFullName::class)
        );
        $this->assertEquals($expected_result, $create->generateProjectFullName());
    }

    public static function fullNameProvider(): array
    {
        return [
            ['joperesr', 'Test project for joperesr' ],
            ['aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'Test project for aaaaaaaaaaaaaaaaaaaaaaa' ],
        ];
    }

    public function testTemplatedValuesAreEscaped(): void
    {
        $rule_project_name = $this->createMock(\Rule_ProjectName::class);
        $rule_project_name->expects(self::once())->method('isValid')->willReturn(true);
        $rule_project_full_name = $this->createMock(\Rule_ProjectFullName::class);
        $rule_project_full_name->expects(self::once())->method('isValid')->willReturn(true);
        $create = new CreateTestProject('</member><foo>', __DIR__ . '/../../../resources/sample-project', $rule_project_name, $rule_project_full_name);
        $create->generateXML();
    }
}
