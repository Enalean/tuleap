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
 *
 */

declare(strict_types=1);

namespace Tuleap\Project\UGroups\Membership;

use PFUser;
use ProjectUGroup;
use Tuleap\Project\Admin\ProjectUGroup\CannotRemoveUserMembershipToUserGroupException;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\DynamicUGroupMembersUpdater;
use Tuleap\Project\UGroups\Membership\StaticUGroups\StaticMemberRemover;

class MemberRemover
{
    /**
     * @var DynamicUGroupMembersUpdater
     */
    private $dynamic_ugroup_members_updater;
    /**
     * @var StaticMemberRemover
     */
    private $static_member_remover;

    public function __construct(DynamicUGroupMembersUpdater $dynamic_ugroup_members_updater, StaticMemberRemover $static_member_remover)
    {
        $this->dynamic_ugroup_members_updater = $dynamic_ugroup_members_updater;
        $this->static_member_remover          = $static_member_remover;
    }

    /**
     * @throws CannotModifyBoundGroupException
     * @throws CannotRemoveUserMembershipToUserGroupException
     */
    public function removeMember(PFUser $user, PFUser $project_administrator, ProjectUGroup $ugroup): void
    {
        if ($ugroup->isBound()) {
            throw new CannotModifyBoundGroupException();
        }

        if ($ugroup->isStatic()) {
            $this->static_member_remover->removeUser($ugroup, $user);
            return;
        }

        $this->dynamic_ugroup_members_updater->removeUser($ugroup->getProject(), $ugroup, $user, $project_administrator);
    }
}
