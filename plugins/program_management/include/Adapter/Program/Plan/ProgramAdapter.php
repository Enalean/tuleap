<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Plan;

use Project_AccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\ProgramStore;
use Tuleap\Project\ProjectAccessChecker;

final class ProgramAdapter implements BuildProgram
{
    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var ProgramStore
     */
    private $program_store;
    /**
     * @var ProjectAccessChecker
     */
    private $project_access_checker;

    public function __construct(
        \ProjectManager $project_manager,
        ProjectAccessChecker $project_access_checker,
        ProgramStore $program_store
    ) {
        $this->project_manager        = $project_manager;
        $this->project_access_checker = $project_access_checker;
        $this->program_store          = $program_store;
    }

    /**
     * @throws ProjectIsNotAProgramException
     * @throws ProgramAccessException
     */
    public function ensureProgramIsAProject(int $project_id, \PFUser $user): void
    {
        $this->ensureUserCanAccessToProject($project_id, $user);
        if (! $this->program_store->isProjectAProgramProject($project_id)) {
            throw new ProjectIsNotAProgramException($project_id);
        }
    }

    /**
     * @throws ProgramAccessException
     * @throws ProjectIsNotAProgramException
     */
    public function ensureProgramIsAProjectForManagement(int $id, \PFUser $user): void
    {
        $this->ensureUserIsAdminOfProject($id, $user);
        $this->ensureProgramIsAProject($id, $user);
    }

    /**
     * @throws ProgramAccessException
     */
    public function ensureProgramIsProjectAndUserIsAdminOf(int $id, \PFUser $user): void
    {
        $this->ensureUserIsAdminOfProject($id, $user);
    }

    /**
     * @throws ProgramAccessException
     */
    private function ensureUserIsAdminOfProject(int $id, \PFUser $user): void
    {
        $this->ensureUserCanAccessToProject($id, $user);
        if (! $user->isAdmin($id)) {
            throw new ProgramAccessException();
        }
    }

    /**
     * @throws ProgramAccessException
     */
    private function ensureUserCanAccessToProject(int $id, \PFUser $user): void
    {
        $project = $this->project_manager->getProject($id);
        try {
            $this->project_access_checker->checkUserCanAccessProject($user, $project);
        } catch (Project_AccessException $exception) {
            throw new ProgramAccessException();
        }
    }
}
