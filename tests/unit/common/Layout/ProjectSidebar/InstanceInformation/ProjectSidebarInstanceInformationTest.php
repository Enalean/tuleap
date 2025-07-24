<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Layout\ProjectSidebar\InstanceInformation;

use Tuleap\BuildVersion\FlavorFinder;
use Tuleap\Glyph\GlyphFinder;
use Tuleap\Layout\Logo\IDetectIfLogoIsCustomized;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProjectSidebarInstanceInformationTest extends TestCase
{
    public function testBuildsWithoutCopyright(): void
    {
        $base_language = $this->createStub(\BaseLanguage::class);
        $base_language->method('hasText')->willReturn(false);
        $representation = $this->buildsRepresentation($base_language);
        self::assertNull($representation->copyright);
    }

    public function testBuildsWithCustomizedCopyrightNotice(): void
    {
        $base_language = $this->createStub(\BaseLanguage::class);
        $base_language->method('hasText')->willReturn(true);
        $expected_copyright = 'Some copyright notice';
        $base_language->method('getOverridableText')->willReturn($expected_copyright);
        $representation = $this->buildsRepresentation($base_language);
        self::assertEquals($expected_copyright, $representation->copyright);
    }

    private function buildsRepresentation(\BaseLanguage $language): ProjectSidebarInstanceInformation
    {
        return ProjectSidebarInstanceInformation::build(
            $language,
            new class implements FlavorFinder {
                #[\Override]
                public function isEnterprise(): bool
                {
                    return false;
                }
            },
            new class implements IDetectIfLogoIsCustomized {
                #[\Override]
                public function isLegacyOrganizationLogoCustomized(): bool
                {
                    return false;
                }

                #[\Override]
                public function isSvgOrganizationLogoCustomized(): bool
                {
                    return false;
                }
            },
            $this->createStub(GlyphFinder::class)
        );
    }
}
