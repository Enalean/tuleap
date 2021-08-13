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
use Tuleap\ProgramManagement\Domain\Permissions\PermissionBypass;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\VerifyIsProgram;
use Tuleap\ProgramManagement\Domain\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\Project\ProjectAccessChecker;

final class ProgramAdapter implements BuildProgram
{
    public function __construct(
        private \ProjectManager $project_manager,
        private ProjectAccessChecker $project_access_checker,
        private VerifyIsProgram $program_verifier,
        private RetrieveUser $user_manager_adapter
    ) {
    }

    public function ensureProgramIsAProject(int $project_id, UserIdentifier $user, ?PermissionBypass $bypass): void
    {
        $this->ensureUserCanAccessToProject($project_id, $user, $bypass);
        if (! $this->program_verifier->isAProgram($project_id)) {
            throw new ProjectIsNotAProgramException($project_id);
        }
    }

    /**
     * @throws ProgramAccessException
     */
    private function ensureUserCanAccessToProject(int $id, UserIdentifier $user_identifier, ?PermissionBypass $bypass): void
    {
        if ($bypass) {
            return;
        }

        $project = $this->project_manager->getProject($id);
        $user    = $this->user_manager_adapter->getUserWithId($user_identifier);
        try {
            $this->project_access_checker->checkUserCanAccessProject($user, $project);
        } catch (Project_AccessException $exception) {
            throw new ProgramAccessException($id, $this->user_manager_adapter, $user_identifier);
        }
    }
}
