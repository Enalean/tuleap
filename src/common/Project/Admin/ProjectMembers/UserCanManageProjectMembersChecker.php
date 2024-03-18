<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\ProjectMembers;

use Tuleap\Project\Admin\MembershipDelegationDao;

final class UserCanManageProjectMembersChecker implements EnsureUserCanManageProjectMembers
{
    public function __construct(private MembershipDelegationDao $delegation_dao)
    {
    }

    public function checkUserCanManageProjectMembers(\PFUser $user, \Project $project): void
    {
        if ($project->getStatus() === \Project::STATUS_CREATING_FROM_ARCHIVE) {
            throw new UserIsNotAllowedToManageProjectMembersException();
        }

        if ($user->isAdmin((int) $project->getID())) {
            return;
        }

        if ($this->delegation_dao->doesUserHasMembershipDelegation((int) $user->getId(), (int) $project->getID())) {
            return;
        }

        throw new UserIsNotAllowedToManageProjectMembersException();
    }
}
