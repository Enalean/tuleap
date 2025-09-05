<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Plan;

use Project_AccessException;
use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveFullProject;
use Tuleap\ProgramManagement\Domain\Permissions\PermissionBypass;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\RetrieveProjectUgroupsCanPrioritizeItems;
use Tuleap\ProgramManagement\Domain\Program\Plan\VerifyPrioritizeFeaturesPermission;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Domain\Workspace\VerifyUserIsProgramAdmin;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\Project\CheckProjectAccess;

final class PrioritizeFeaturesPermissionVerifier implements VerifyPrioritizeFeaturesPermission
{
    public function __construct(
        private RetrieveFullProject $retrieve_full_project,
        private CheckProjectAccess $project_access_checker,
        private RetrieveProjectUgroupsCanPrioritizeItems $can_prioritize_features_dao,
        private RetrieveUser $user_manager,
        private VerifyUserIsProgramAdmin $verify_user_is_program_admin,
    ) {
    }

    #[\Override]
    public function canUserPrioritizeFeatures(
        ProgramIdentifier $program,
        UserIdentifier $user_identifier,
        ?PermissionBypass $bypass,
    ): bool {
        if ($bypass) {
            return true;
        }

        if ($this->verify_user_is_program_admin->isUserProgramAdmin($user_identifier, $program)) {
            return true;
        }

        $user       = $this->user_manager->getUserWithId($user_identifier);
        $program_id = $program->getId();
        $project    = $this->retrieve_full_project->getProject($program_id);
        try {
            $this->project_access_checker->checkUserCanAccessProject($user, $project);
        } catch (Project_AccessException $exception) {
            return false;
        }

        $ugroups_that_can_prioritize = $this->can_prioritize_features_dao->searchUserGroupIDsWhoCanPrioritizeFeaturesByProjectID($program_id);

        foreach ($ugroups_that_can_prioritize as $ugroup_that_can_prioritize) {
            if ($user->isMemberOfUGroup($ugroup_that_can_prioritize, $program_id)) {
                return true;
            }
        }

        return false;
    }
}
