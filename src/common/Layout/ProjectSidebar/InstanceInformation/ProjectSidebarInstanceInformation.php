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

/**
 * @psalm-immutable
 */
final class ProjectSidebarInstanceInformation
{
    private function __construct(
        public ProjectSidebarInstanceVersionInformation $version,
        public ?string $copyright,
        public ProjectSidebarLogoInformation $logo,
    ) {
    }

    public static function build(
        \BaseLanguage $language,
        FlavorFinder $flavor_finder,
        IDetectIfLogoIsCustomized $customized_logo_detector,
        GlyphFinder $glyph_finder,
    ): self {
        $copyright = null;
        if ($language->hasText('global', 'copyright')) {
            $copyright = $language->getOverridableText('global', 'copyright');
        }

        return new self(
            ProjectSidebarInstanceVersionInformation::fromFlavorFinder($flavor_finder),
            $copyright,
            ProjectSidebarLogoInformation::fromDetectorAndGlyphFinder($customized_logo_detector, $glyph_finder)
        );
    }
}
