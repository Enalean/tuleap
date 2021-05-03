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

namespace Tuleap\ProgramManagement\Adapter\Program\Plan;

use Project_AccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\VerifyPrioritizeFeaturesPermission;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\Project\ProjectAccessChecker;

class PrioritizeFeaturesPermissionVerifier implements VerifyPrioritizeFeaturesPermission
{
    /**
     * @var CanPrioritizeFeaturesDAO
     */
    private $can_prioritize_features_dao;
    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var ProjectAccessChecker
     */
    private $project_access_checker;

    public function __construct(
        \ProjectManager $project_manager,
        ProjectAccessChecker $project_access_checker,
        CanPrioritizeFeaturesDAO $can_prioritize_features_dao
    ) {
        $this->project_manager             = $project_manager;
        $this->project_access_checker      = $project_access_checker;
        $this->can_prioritize_features_dao = $can_prioritize_features_dao;
    }

    public function canUserPrioritizeFeatures(ProgramIdentifier $program, \PFUser $user): bool
    {
        $program_id = $program->getId();
        $project    = $this->project_manager->getProject($program_id);
        try {
            $this->project_access_checker->checkUserCanAccessProject($user, $project);
        } catch (Project_AccessException $exception) {
            return false;
        }

        if ($user->isAdmin($program_id)) {
            return true;
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
