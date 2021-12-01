<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Admin;

use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Team\VerifyIsTeam;
use Tuleap\ProgramManagement\Domain\Workspace\ProjectIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserReference;
use Tuleap\ProgramManagement\Domain\Workspace\VerifyProjectPermission;

/**
 * I am a Tuleap Project ID (identifier) being configured for program management.
 * Contrary to ProgramIdentifier, I may have zero Team projects because the admin is still adding the teams.
 * I cannot be a Project that is already associated with another Program (a Team project).
 * @psalm-immutable
 * @see ProgramIdentifier
 */
final class ProgramForAdministrationIdentifier
{
    private function __construct(public int $id)
    {
    }

    /**
     * @throws ProgramCannotBeATeamException
     * @throws ProgramAccessException
     */
    public static function fromProject(
        VerifyIsTeam $team_verifier,
        VerifyProjectPermission $administrator_verifier,
        UserReference $user,
        ProjectIdentifier $project_identifier,
    ): self {
        $project_id = $project_identifier->getId();
        if ($team_verifier->isATeam($project_id)) {
            throw new ProgramCannotBeATeamException($project_id);
        }
        if (! $administrator_verifier->isProjectAdministrator($user, $project_identifier)) {
            throw new ProgramAccessException($project_id, $user);
        }
        return new self($project_id);
    }
}
