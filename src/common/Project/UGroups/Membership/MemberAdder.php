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

namespace Tuleap\Project\UGroups\Membership;

use ForgeConfig;
use PFUser;
use Project;
use ProjectUGroup;
use Tuleap\Project\Admin\ProjectUGroup\CannotAddRestrictedUserToProjectNotAllowingRestricted;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\DynamicUGroupMembersUpdater;
use Tuleap\Project\UGroups\Membership\StaticUGroups\StaticMemberAdder;
use UGroup_Invalid_Exception;

class MemberAdder
{
    /** @var MembershipUpdateVerifier */
    private $membership_update_verifier;
    /** @var StaticMemberAdder */
    private $static_member_adder;
    /** @var DynamicUGroupMembersUpdater */
    private $dynamic_member_updater;

    public function __construct(
        MembershipUpdateVerifier $membership_update_verifier,
        StaticMemberAdder $static_member_adder,
        DynamicUGroupMembersUpdater $dynamic_member_updater
    ) {
        $this->membership_update_verifier = $membership_update_verifier;
        $this->static_member_adder        = $static_member_adder;
        $this->dynamic_member_updater     = $dynamic_member_updater;
    }

    /**
     * @throws InvalidProjectException
     * @throws UGroup_Invalid_Exception
     * @throws UserIsAnonymousException
     */
    public function addMember(PFUser $user, ProjectUGroup $ugroup): void
    {
        $this->membership_update_verifier->assertUGroupAndUserValidity($user, $ugroup);
        $project = $ugroup->getProject();
        if ($project === null) {
            throw new UGroup_Invalid_Exception();
        }

        if ($project->getAccess() === Project::ACCESS_PRIVATE_WO_RESTRICTED
            && ForgeConfig::areRestrictedUsersAllowed()
            && $user->isRestricted()
        ) {
            throw new CannotAddRestrictedUserToProjectNotAllowingRestricted($user, $project);
        }

        if (! $ugroup->isStatic()) {
            $this->dynamic_member_updater->addUser($project, $ugroup, $user);
            return;
        }

        $project_id = $ugroup->getProjectId();
        $ugroup_id  = $ugroup->getId();
        if ($ugroup->exists($project_id, $ugroup_id)) {
            $this->static_member_adder->addUserToStaticGroup($project_id, $ugroup_id, $user->getId());
        } else {
            throw new UGroup_Invalid_Exception();
        }
    }
}
