<?php
/**
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

namespace Tuleap\Git\ForkRepositories;

use Project;

final readonly class ForkRepositoriesUrlsBuilder
{
    public static function buildGETForksAndDestinationSelectionURL(Project $project): string
    {
        return '/projects/' . urlencode($project->getUnixNameLowerCase()) . '/fork-repositories/';
    }

    public static function buildPOSTForksPermissionsURL(Project $project): string
    {
        return '/projects/' . urlencode($project->getUnixNameLowerCase()) . '/fork-repositories/permissions/';
    }

    public static function buildPOSTDoForksRepositoriesURL(Project $project): string
    {
        return '/plugins/git/?' . http_build_query([
            'group_id' => $project->getID(),
            'action' => 'do_fork_repositories',
        ]);
    }
}
