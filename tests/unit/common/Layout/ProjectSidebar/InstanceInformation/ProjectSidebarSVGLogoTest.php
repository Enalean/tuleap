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

use Tuleap\Glyph\Glyph;
use Tuleap\Glyph\GlyphFinder;
use Tuleap\Layout\Logo\IDetectIfLogoIsCustomized;
use Tuleap\Test\PHPUnit\TestCase;

final class ProjectSidebarSVGLogoTest extends TestCase
{
    public function testBuildsRepresentationWithoutCustomization(): void
    {
        $representation = $this->buildRepresentation(
            new class implements IDetectIfLogoIsCustomized {
                public function isLegacyOrganizationLogoCustomized(): bool
                {
                    return false;
                }

                public function isSvgOrganizationLogoCustomized(): bool
                {
                    return false;
                }
            }
        );

        self::assertNull($representation);
    }

    public function testBuildsRepresentationWithCustomization(): void
    {
        $representation = $this->buildRepresentation(
            new class implements IDetectIfLogoIsCustomized {
                public function isLegacyOrganizationLogoCustomized(): bool
                {
                    return false;
                }

                public function isSvgOrganizationLogoCustomized(): bool
                {
                    return true;
                }
            }
        );

        self::assertNotEmpty($representation->normal);
        self::assertNotEmpty($representation->small);
    }

    private function buildRepresentation(IDetectIfLogoIsCustomized $customized_logo_detector): ?ProjectSidebarSVGLogo
    {
        $glyph_finder = $this->createStub(GlyphFinder::class);
        $glyph        = new Glyph('<svg>Some content</svg>');
        $glyph_finder->method('get')->willReturn($glyph);
        return ProjectSidebarSVGLogo::fromDetectorAndGlyphFinder($customized_logo_detector, $glyph_finder);
    }
}
