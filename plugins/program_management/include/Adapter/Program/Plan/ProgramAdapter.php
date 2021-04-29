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

use Project;
use Project_AccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Program;
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
    public function buildExistingProgramProject(int $id, \PFUser $user): Program
    {
        $project = $this->getProject($id, $user);
        $this->ensureProgramIsAProject($project);

        return new Program((int) $project->getID());
    }

    /**
     * @throws ProjectIsNotAProgramException
     * @throws ProgramAccessException
     */
    public function buildExistingProgramProjectForManagement(int $id, \PFUser $user): ProgramForManagement
    {
        $project = $this->getProjectForManagement($id, $user);
        $this->ensureProgramIsAProject($project);

        return new ProgramForManagement((int) $project->getID());
    }

    /**
     * @throws ProgramAccessException
     */
    public function buildNewProgramProject(int $id, \PFUser $user): ToBeCreatedProgram
    {
        $project = $this->getProjectForManagement($id, $user);

        return new ToBeCreatedProgram((int) $project->getID());
    }

    /**
     * @throws ProjectIsNotAProgramException
     */
    private function ensureProgramIsAProject(Project $project): void
    {
        if (! $this->program_store->isProjectAProgramProject((int) $project->getId())) {
            throw new ProjectIsNotAProgramException((int) $project->getId());
        }
    }

    private function getProjectForManagement(int $id, \PFUser $user): \Project
    {
        $project = $this->getProject($id, $user);
        if (! $user->isAdmin($id)) {
            throw new ProgramAccessException();
        }

        return $project;
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
