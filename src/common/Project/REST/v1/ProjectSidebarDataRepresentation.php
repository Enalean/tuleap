<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Project\REST\v1;

use Project;

/**
 * @psalm-immutable
 */
final class ProjectSidebarDataRepresentation
{
    private function __construct(public bool $is_collapsed, public string $config)
    {
    }

    public static function fromProjectAndUser(Project $project, \PFUser $user): self
    {
        return new self(
            $user->getPreference('sidebar_state') !== 'sidebar-expanded',
            "to_be_done: " . $project->getID()
        );
    }
}
