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
 */

declare(strict_types=1);

namespace Tuleap\BuildVersion;

use PHPUnit\Framework\TestCase;

final class VersionPresenterTest extends TestCase
{
    public function testBuildsEnterpriseVersionPresenter(): void
    {
        $version = VersionPresenter::fromFlavorFinder(
            new class implements FlavorFinder
            {
                public function isEnterprise(): bool
                {
                    return true;
                }
            }
        );

        $this->assertStringContainsString('Enterprise', $version->flavor_name);
        $this->assertNotEmpty($version->version_identifier);

        $full_descriptive_version = $version->getFullDescriptiveVersion();
        $this->assertStringContainsString($version->flavor_name, $full_descriptive_version);
        $this->assertStringContainsString($version->version_identifier, $full_descriptive_version);
        $this->assertEquals(trim(file_get_contents(__DIR__ . '/../../../../VERSION')), $version->version_number);
    }

    public function testBuildsCommunityVersionPresenter(): void
    {
        $version = VersionPresenter::fromFlavorFinder(
            new class implements FlavorFinder
            {
                public function isEnterprise(): bool
                {
                    return false;
                }
            }
        );

        $this->assertStringContainsString('Community', $version->flavor_name);
        $this->assertStringContainsString('Dev Build', $version->version_identifier);

        $full_descriptive_version = $version->getFullDescriptiveVersion();
        $this->assertStringContainsString($version->flavor_name, $full_descriptive_version);
        $this->assertStringContainsString($version->version_identifier, $full_descriptive_version);
        $this->assertEquals(trim(file_get_contents(__DIR__ . '/../../../../VERSION')), $version->version_number);
    }
}
