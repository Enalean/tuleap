<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Test\Stubs\include;

use PFUser;
use Project;
use Project_AccessNotAdminException;
use Project_AccessPrivateException;
use Tuleap\include\CheckUserCanAccessProject;
use Tuleap\include\CheckUserCanAccessProjectAndIsAdmin;

final class CheckUserCanAccessProjectStub implements CheckUserCanAccessProject, CheckUserCanAccessProjectAndIsAdmin
{
    /**
     * array<user_id, project_id[]>
     * @var array<int, int[]>
     */
    private array $private_projects_per_user = [];
    /**
     * array<user_id, project_id[]>
     * @var array<int, int[]>
     */
    private array $admins_users = [];

    public static function build(): self
    {
        return new self();
    }

    public function withPrivateProjectForUser(Project $project, PFUser $user): self
    {
        if (! isset($this->private_projects_per_user[(int) $user->getId()])) {
            $this->private_projects_per_user[(int) $user->getId()] = [];
        }

        $this->private_projects_per_user[(int) $user->getId()][] = (int) $project->getID();

        return $this;
    }

    public function userCanAccessProject(PFUser $user, Project $project): bool
    {
        if (
            isset($this->private_projects_per_user[(int) $user->getId()])
            && in_array((int) $project->getID(), $this->private_projects_per_user[(int) $user->getId()])
        ) {
            throw new Project_AccessPrivateException();
        }

        return true;
    }

    public function withUserAdminOf(PFUser $user, Project $project): self
    {
        if (! isset($this->admins_users[(int) $user->getId()])) {
            $this->admins_users[(int) $user->getId()] = [];
        }

        $this->admins_users[(int) $user->getId()][] = (int) $project->getID();

        return $this;
    }

    public function userCanAccessProjectAndIsProjectAdmin(PFUser $user, Project $project): void
    {
        if (! isset($this->admins_users[(int) $user->getId()]) || ! in_array((int) $project->getID(), $this->admins_users[(int) $user->getId()])) {
            throw new Project_AccessNotAdminException();
        }
    }
}
