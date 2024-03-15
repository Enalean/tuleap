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
use Tuleap\ServerHostname;

/**
 * @psalm-immutable
 */
final class ProjectSidebarLogoInformation
{
    private function __construct(
        public string $logo_link_href,
        public ?ProjectSidebarSVGLogo $svg,
        public ?ProjectSidebarLegacyPNGHref $legacy_png_href,
    ) {
    }

    public static function fromDetectorAndGlyphFinder(IDetectIfLogoIsCustomized $customized_logo_detector, GlyphFinder $glyph_finder): self
    {
        return new self(
            ServerHostname::HTTPSUrl(),
            ProjectSidebarSVGLogo::fromDetectorAndGlyphFinder($customized_logo_detector, $glyph_finder),
            ProjectSidebarLegacyPNGHref::fromDetector($customized_logo_detector),
        );
    }
}
