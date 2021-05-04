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
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramForManagement;
use Tuleap\ProgramManagement\Domain\Program\ProgramStore;
use Tuleap\ProgramManagement\Domain\Program\ToBeCreatedProgram;
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
    public function buildExistingProgramProject(int $id, \PFUser $user): ProgramIdentifier
    {
        $project = $this->getProject($id, $user);

        return ProgramIdentifier::fromId($this, (int) $project->getID());
    }

    /**
     * @throws ProjectIsNotAProgramException
     * @throws ProgramAccessException
     */
    public function buildExistingProgramProjectForManagement(int $id, \PFUser $user): ProgramForManagement
    {
        return ProgramForManagement::fromId($this, $id, $user);
    }

    /**
     * @throws ProgramAccessException
     */
    public function buildNewProgramProject(int $id, \PFUser $user): ToBeCreatedProgram
    {
        $this->ensureUserIsAdminOfProject($id, $user);
        return new ToBeCreatedProgram($id);
    }

    /**
     * @throws ProjectIsNotAProgramException
     */
    public function ensureProgramIsAProject(int $project_id): void
    {
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
        $this->ensureProgramIsAProject($id);
    }

    /**
     * @throws ProgramAccessException
     */
    private function ensureUserIsAdminOfProject(int $id, \PFUser $user): void
    {
        $this->getProject($id, $user);
        if (! $user->isAdmin($id)) {
            throw new ProgramAccessException();
        }
    }

    private function getProject(int $id, \PFUser $user): \Project
    {
        $project = $this->project_manager->getProject($id);
        try {
            $this->project_access_checker->checkUserCanAccessProject($user, $project);
        } catch (Project_AccessException $exception) {
            throw new ProgramAccessException();
        }

        return $project;
    }
}
