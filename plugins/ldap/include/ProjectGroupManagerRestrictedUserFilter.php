<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\LDAP;

use ForgeConfig;
use Project;
use UserManager;

final class ProjectGroupManagerRestrictedUserFilter
{
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(UserManager $user_manager)
    {
        $this->user_manager = $user_manager;
    }

    public function filter(Project $project, LDAPSetOfUserIDsForDiff $set_of_user_ids): LDAPSetOfUserIDsForDiff
    {
        if (! ForgeConfig::areRestrictedUsersAllowed()) {
            return $set_of_user_ids;
        }

        if ($this->areRestrictedUsersAllowed($project)) {
            return $set_of_user_ids;
        }

        $set_of_user_ids = $this->clearRestrictedUsersToAddInAProjectNotIncludingRestrictedContext($set_of_user_ids);
        return $this->addInUsersToRemoveUsersThatAreRestricted($project, $set_of_user_ids);
    }

    private function areRestrictedUsersAllowed(Project $project): bool
    {
        $project_access = $project->getAccess();
        return $project_access === Project::ACCESS_PUBLIC_UNRESTRICTED || $project_access === Project::ACCESS_PRIVATE ||
            $project->isSuperPublic();
    }

    private function clearRestrictedUsersToAddInAProjectNotIncludingRestrictedContext(LDAPSetOfUserIDsForDiff $set_of_user_ids): LDAPSetOfUserIDsForDiff
    {
        $user_ids_to_add = [];
        foreach ($set_of_user_ids->getUserIDsToAdd() as $user_id) {
            $user = $this->user_manager->getUserById($user_id);
            if ($user !== null && ! $user->isRestricted()) {
                $user_ids_to_add[] = $user_id;
            }
        }

        return new LDAPSetOfUserIDsForDiff(
            $user_ids_to_add,
            $set_of_user_ids->getUserIDsToRemove(),
            $set_of_user_ids->getUserIDsNotImpacted()
        );
    }

    private function addInUsersToRemoveUsersThatAreRestricted(Project $project, LDAPSetOfUserIDsForDiff $set_of_user_ids): LDAPSetOfUserIDsForDiff
    {
        $extra_user_id_to_delete = [];
        foreach ($project->getMembers() as $member) {
            $member_id = $member->getId();
            if ($member->isRestricted()) {
                $extra_user_id_to_delete[$member_id] = $member_id;
            }
        }

        $refreshed_not_impacted_user_ids = [];
        foreach ($set_of_user_ids->getUserIDsNotImpacted() as $user_id_not_impacted) {
            if (! isset($extra_user_id_to_delete[$user_id_not_impacted])) {
                $refreshed_not_impacted_user_ids[] = $user_id_not_impacted;
            }
        }

        return new LDAPSetOfUserIDsForDiff(
            $set_of_user_ids->getUserIDsToAdd(),
            array_unique(array_merge($set_of_user_ids->getUserIDsToRemove(), $extra_user_id_to_delete)),
            $refreshed_not_impacted_user_ids
        );
    }
}
