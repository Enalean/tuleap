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

namespace Tuleap\Git;

use Git_Backend_Interface;
use GitDao;
use Project;

class GitProjectRenamer
{
    public function __construct(private Git_Backend_Interface $git_backend, private GitDao $git_dao)
    {
    }

    public function renameProject(Project $project, string $new_name): void
    {
        $new_name = strtolower($new_name);
        if ($this->git_backend->renameProject($project, $new_name)) {
            $this->git_dao->renameProject($project, $new_name);
        }
    }
}
