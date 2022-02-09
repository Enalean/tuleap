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
use Tuleap\BuildVersion\VersionPresenter;

/**
 * @psalm-immutable
 */
final class ProjectSidebarInstanceVersionInformation
{
    private function __construct(
        public string $flavor_name,
        public string $version_identifier,
        public string $full_descriptive_version,
    ) {
    }

    public static function fromFlavorFinder(FlavorFinder $flavor_finder): self
    {
        $version_presenter = VersionPresenter::fromFlavorFinder($flavor_finder);

        return new self(
            $version_presenter->flavor_name,
            $version_presenter->version_identifier,
            $version_presenter->getFullDescriptiveVersion()
        );
    }
}
