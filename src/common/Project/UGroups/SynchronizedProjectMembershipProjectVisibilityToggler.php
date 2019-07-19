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
 *
 */

declare(strict_types=1);

namespace Tuleap\Project\UGroups;

use Project;

class SynchronizedProjectMembershipProjectVisibilityToggler
{
    private const ENABLE_ON_PROJECT_VISIBILITY_MATRIX = [
        Project::ACCESS_PRIVATE => [
            Project::ACCESS_PUBLIC              => true,
            Project::ACCESS_PUBLIC_UNRESTRICTED => true,
        ],
        Project::ACCESS_PRIVATE_WO_RESTRICTED => [
            Project::ACCESS_PUBLIC              => true,
            Project::ACCESS_PUBLIC_UNRESTRICTED => true,
        ]
    ];

    /** @var SynchronizedProjectMembershipDao */
    private $dao;

    public function __construct(SynchronizedProjectMembershipDao $dao)
    {
        $this->dao = $dao;
    }

    public function enableAccordingToVisibility(Project $project, string $old_access, string $new_access): void
    {
        if (isset(self::ENABLE_ON_PROJECT_VISIBILITY_MATRIX[$old_access][$new_access])) {
            $this->dao->enable($project);
        }
    }
}
