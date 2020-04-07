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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalLanguageMock;

final class CreateTestProjectTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @dataProvider userNameProvider
     */
    public function testUnixNameIsValid(string $username, string $expected_result): void
    {
        $create = new CreateTestProject(
            $username,
            '/archive/path',
            \Mockery::mock(\Rule_ProjectName::class),
            \Mockery::mock(\Rule_ProjectFullName::class)
        );
        $this->assertEquals($expected_result, $create->generateProjectUnixName());
    }

    public function userNameProvider()
    {
        return [
            ['joperesr', 'test-for-joperesr' ],
            ['jope_resr', 'test-for-jope-resr' ],
            ['jope.resr', 'test-for-jope-resr' ],
        ];
    }

    /**
     * @dataProvider fullNameProvider
     */
    public function testFullNameIsValid(string $username, string $expected_result): void
    {
        $create = new CreateTestProject(
            $username,
            '/archive/path',
            \Mockery::mock(\Rule_ProjectName::class),
            \Mockery::mock(\Rule_ProjectFullName::class)
        );
        $this->assertEquals($expected_result, $create->generateProjectFullName());
    }

    public function fullNameProvider()
    {
        return [
            ['joperesr', 'Test project for joperesr' ],
            ['aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'Test project for aaaaaaaaaaaaaaaaaaaaaaa' ],
        ];
    }

    public function testTemplatedValuesAreEscaped(): void
    {
        $rule_project_name = \Mockery::mock(\Rule_ProjectName::class);
        $rule_project_name->shouldReceive('isValid')->once()->andReturn(true);
        $rule_project_full_name = \Mockery::mock(\Rule_ProjectFullName::class);
        $rule_project_full_name->shouldReceive('isValid')->once()->andReturn(true);
        $create = new CreateTestProject('</member><foo>', __DIR__ . '/../../resources/sample-project', $rule_project_name, $rule_project_full_name);
        $create->generateXML();
    }
}
