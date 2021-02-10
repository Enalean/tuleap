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

use Luracast\Restler\RestException;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\ProgramManagement\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Program\Program;
use Tuleap\ProgramManagement\Program\ProgramStore;
use Tuleap\ProgramManagement\Program\ToBeCreatedProgram;

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

    public function __construct(
        \ProjectManager $project_manager,
        ProgramStore $program_store
    ) {
        $this->project_manager = $project_manager;
        $this->program_store   = $program_store;
    }

    /**
     * @throws ProjectIsNotAProgramException
     * @throws ProgramAccessException
     */
    public function buildExistingProgramProject(int $id, \PFUser $user): Program
    {
        $project = $this->buildProject($id, $user);

        if (! $this->program_store->isProjectAProgramProject((int) $project->getId())) {
            throw new ProjectIsNotAProgramException((int) $project->getId());
        }

        return new Program((int) $project->getID());
    }

    /**
     * @throws ProgramAccessException
     */
    public function buildNewProgramProject(int $id, \PFUser $user): ToBeCreatedProgram
    {
        $project = $this->buildProject($id, $user);

        return new ToBeCreatedProgram((int) $project->getID());
    }

    private function buildProject(int $id, \PFUser $user): \Project
    {
        $project = $this->project_manager->getProject($id);
        try {
            ProjectAuthorization::userCanAccessProjectAndIsProjectAdmin($user, $project);
        } catch (RestException $exception) {
            throw new ProgramAccessException();
        }

        return $project;
    }
}
