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

namespace Tuleap\Project\UGroups\Membership\DynamicUGroups;

use Tuleap\Project\Admin\ProjectMembers\UserIsNotAllowedToManageProjectMembersException;
use Tuleap\Project\Admin\ProjectUGroup\CannotAddRestrictedUserToProjectNotAllowingRestricted;

interface ProjectMemberAdder
{
    public function addProjectMemberWithFeedback(\PFUser $user, \Project $project, \PFUser $project_admin): void;

    /**
     * @throws UserIsNotActiveOrRestrictedException
     * @throws CannotAddRestrictedUserToProjectNotAllowingRestricted
     * @throws AlreadyProjectMemberException
     * @throws NoEmailForUserException
     * @throws UserIsNotAllowedToManageProjectMembersException
     */
    public function addProjectMember(\PFUser $user, \Project $project, \PFUser $project_admin): void;
}
