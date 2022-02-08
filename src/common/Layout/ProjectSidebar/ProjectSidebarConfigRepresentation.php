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

namespace Tuleap\Layout\ProjectSidebar;

use Tuleap\BuildVersion\FlavorFinder;
use Tuleap\Glyph\GlyphFinder;
use Tuleap\Layout\Logo\IDetectIfLogoIsCustomized;
use Tuleap\Layout\ProjectSidebar\InstanceInformation\ProjectSidebarInstanceInformation;
use Tuleap\Layout\ProjectSidebar\User\ProjectSidebarUser;

/**
 * @psalm-immutable
 */
final class ProjectSidebarConfigRepresentation
{
    private function __construct(
        public ProjectSidebarUser $user,
        public ProjectSidebarInstanceInformation $instance_information,
    ) {
    }

    public static function build(
        \Project $project,
        \PFUser $user,
        FlavorFinder $flavor_finder,
        IDetectIfLogoIsCustomized $customized_logo_detector,
        GlyphFinder $glyph_finder,
    ): self {
        return new self(
            ProjectSidebarUser::fromProjectAndUser(
                $project,
                $user,
            ),
            ProjectSidebarInstanceInformation::build(
                $user->getLanguage(),
                $flavor_finder,
                $customized_logo_detector,
                $glyph_finder,
            ),
        );
    }
}
