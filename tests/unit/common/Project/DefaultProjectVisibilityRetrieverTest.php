<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Project;

use ForgeAccess;
use ForgeConfig;
use PHPUnit\Framework\TestCase;
use Project;
use Tuleap\ForgeConfigSandbox;

final class DefaultProjectVisibilityRetrieverTest extends TestCase
{
    use ForgeConfigSandbox;

    /**
     * @dataProvider providerLegacySetting
     */
    public function testLegacyIsProjectPublicSettingIsSupported(int $setting_value, string $expected_access): void
    {
        ForgeConfig::set('sys_is_project_public', $setting_value);

        $default_visibility_retriever = new DefaultProjectVisibilityRetriever();

        $this->assertEquals($expected_access, $default_visibility_retriever->getDefaultProjectVisibility());
    }

    public function providerLegacySetting(): array
    {
        return [
            [0, Project::ACCESS_PRIVATE],
            [1, Project::ACCESS_PUBLIC],
        ];
    }

    /**
     * @dataProvider providerSettingDefaultVisibility
     */
    public function testAllPossibleProjectVisibilityCanBeSetAsTheDefaultValue(
        string $setting_value,
        bool $are_restricted_allowed,
        string $expected_access
    ): void {
        if ($are_restricted_allowed) {
            ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        }
        ForgeConfig::set(DefaultProjectVisibilityRetriever::CONFIG_SETTING_NAME, $setting_value);

        $default_visibility_retriever = new DefaultProjectVisibilityRetriever();

        $this->assertEquals($expected_access, $default_visibility_retriever->getDefaultProjectVisibility());
    }

    public function providerSettingDefaultVisibility(): array
    {
        return [
            [Project::ACCESS_PUBLIC, true, Project::ACCESS_PUBLIC],
            [Project::ACCESS_PUBLIC, false, Project::ACCESS_PUBLIC],
            [Project::ACCESS_PUBLIC_UNRESTRICTED, true, Project::ACCESS_PUBLIC_UNRESTRICTED],
            [Project::ACCESS_PUBLIC_UNRESTRICTED, false, Project::ACCESS_PUBLIC],
            [Project::ACCESS_PRIVATE, true, Project::ACCESS_PRIVATE],
            [Project::ACCESS_PRIVATE, false, Project::ACCESS_PRIVATE],
            [Project::ACCESS_PRIVATE_WO_RESTRICTED, true, Project::ACCESS_PRIVATE_WO_RESTRICTED],
            [Project::ACCESS_PRIVATE_WO_RESTRICTED, false, Project::ACCESS_PRIVATE],
        ];
    }

    public function testFallbackToDefaultValueWhenNoSettingIsFound(): void
    {
        $default_visibility_retriever = new DefaultProjectVisibilityRetriever();

        $this->assertEquals(Project::ACCESS_PUBLIC, $default_visibility_retriever->getDefaultProjectVisibility());
    }

    public function testNewSettingHasPrecedenceOverTheLegacySetting(): void
    {
        ForgeConfig::set(DefaultProjectVisibilityRetriever::CONFIG_SETTING_NAME, Project::ACCESS_PRIVATE);
        ForgeConfig::set('sys_is_project_public', 1);

        $default_visibility_retriever = new DefaultProjectVisibilityRetriever();

        $this->assertEquals(Project::ACCESS_PRIVATE, $default_visibility_retriever->getDefaultProjectVisibility());
    }
}
