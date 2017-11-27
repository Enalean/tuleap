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

use EventManager;
use PFUser;
use Project;
use ProjectUGroup;
use Tuleap\Project\UserPermissionsDao;
use UGroupBinding;

class DynamicUGroupMembersUpdater
{
    /**
     * @var UserPermissionsDao
     */
    private $user_permissions_dao;
    /**
     * @var UGroupBinding
     */
    private $ugroup_binding;

    public function __construct(
        UserPermissionsDao $user_permissions_dao,
        UGroupBinding $ugroup_binding
    ) {
        $this->user_permissions_dao = $user_permissions_dao;
        $this->ugroup_binding       = $ugroup_binding;
    }

    public function addUser(Project $project, ProjectUGroup $ugroup, PFUser $user)
    {
        if ((int) $ugroup->getId() !== ProjectUGroup::PROJECT_ADMIN) {
            return;
        }

        if (! $user->isMember($project->getID())) {
            account_add_user_to_group($project->getID(), $user->getUserName());
            $this->ugroup_binding->reloadUgroupBindingInProject($project);
        }

        $this->user_permissions_dao->addUserAsProjectAdmin($project->getID(), $user->getId());
        EventManager::instance()->processEvent(new UserBecomesProjectAdmin($project, $user));
    }

    public function removeUser(Project $project, ProjectUGroup $ugroup, PFUser $user)
    {
        if ((int) $ugroup->getId() !== ProjectUGroup::PROJECT_ADMIN) {
            return;
        }

        $this->user_permissions_dao->removeUserFromProjectAdmin($project->getID(), $user->getId());
        EventManager::instance()->processEvent(new UserIsNoLongerProjectAdmin($project, $user));
    }
}
