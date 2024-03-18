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

use Tuleap\Glyph\GlyphFinder;
use Tuleap\Layout\Logo\IDetectIfLogoIsCustomized;

/**
 * @psalm-immutable
 */
final class ProjectSidebarSVGLogo
{
    public function __construct(
        public string $normal,
        public string $small,
    ) {
    }

    public static function fromDetectorAndGlyphFinder(IDetectIfLogoIsCustomized $customized_logo_detector, GlyphFinder $glyph_finder): ?self
    {
        if (! $customized_logo_detector->isSvgOrganizationLogoCustomized()) {
            return null;
        }

        return new self(
            $glyph_finder->get('organization_logo')->getInlineString(),
            $glyph_finder->get('organization_logo_small')->getInlineString(),
        );
    }
}
