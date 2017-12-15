<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Project\Admin\ProjectUGroup;

use PFUser;
use Project;
use ProjectUGroup;
use Tuleap\Project\UserPermissionsDao;
use User_ForgeUserGroupUsersDao;

class UserIsUGroupMemberChecker
{
    /**
     * @var UserPermissionsDao
     */
    private $user_permissions_dao;
    /**
     * @var User_ForgeUserGroupUsersDao
     */
    private $ugroup_dao;

    public function __construct(UserPermissionsDao $user_permissions_dao, User_ForgeUserGroupUsersDao $ugroup_dao)
    {
        $this->user_permissions_dao = $user_permissions_dao;
        $this->ugroup_dao           = $ugroup_dao;
    }

    public function isUserPartOfUgroupMembers(Project $project, ProjectUGroup $ugroup, PFUser $user)
    {
        if ($ugroup->isStatic()) {
            return $this->isUserPartOfStaticUgroupMembers($ugroup, $user);
        } else {
            return $this->isUserPartOfDynamicUgroupMembers($project, $ugroup, $user);
        }
    }

    private function isUserPartOfStaticUgroupMembers(ProjectUGroup $ugroup, PFUser $user)
    {
        return $this->ugroup_dao->isUserInGroup($user->getId(), $ugroup->getId());
    }

    private function isUserPartOfDynamicUgroupMembers(Project $project, ProjectUGroup $ugroup, PFUser $user)
    {
        switch ($ugroup->getId()) {
            case ProjectUGroup::PROJECT_MEMBERS:
                return $this->user_permissions_dao->isUserPartOfProjectMembers($project->getID(), $user->getId());
                break;
            case ProjectUGroup::PROJECT_ADMIN:
                return $this->user_permissions_dao->isUserPartOfProjectAdmins($project->getID(), $user->getId());
                break;
            case ProjectUGroup::WIKI_ADMIN:
                return $this->user_permissions_dao->isUserPartOfWikiAdmins($project->getID(), $user->getId());
                break;
            case ProjectUGroup::FORUM_ADMIN:
                return $this->user_permissions_dao->isUserPartOfForumAdmins($project->getID(), $user->getId());
                break;
            case ProjectUGroup::NEWS_WRITER:
                return $this->user_permissions_dao->isUserPartOfNewsEditors($project->getID(), $user->getId());
                break;
            case ProjectUGroup::NEWS_ADMIN:
                return $this->user_permissions_dao->isUserPartOfNewsAdmins($project->getID(), $user->getId());
                break;
        }
    }
}
