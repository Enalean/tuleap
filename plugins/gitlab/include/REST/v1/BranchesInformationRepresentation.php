<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\REST\v1;

use Tuleap\Gitlab\API\GitlabProject;

/**
 * @psalm-immutable
 */
final class BranchesInformationRepresentation
{
    public string $default_branch;

    private function __construct(string $default_branch)
    {
        $this->default_branch = $default_branch;
    }

    public static function fromGitLabProject(GitlabProject $gitlab_project): self
    {
        return new self($gitlab_project->getDefaultBranch());
    }
}
