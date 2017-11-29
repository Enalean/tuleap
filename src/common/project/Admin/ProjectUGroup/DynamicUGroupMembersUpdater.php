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
    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(
        UserPermissionsDao $user_permissions_dao,
        UGroupBinding $ugroup_binding,
        EventManager $event_manager
    ) {
        $this->user_permissions_dao = $user_permissions_dao;
        $this->ugroup_binding       = $ugroup_binding;
        $this->event_manager        = $event_manager;
    }

    public function addUser(Project $project, ProjectUGroup $ugroup, PFUser $user)
    {
        switch ($ugroup->getId()) {
            case ProjectUGroup::PROJECT_ADMIN:
                $this->addProjectAdministrator($project, $user);
                break;
            case ProjectUGroup::WIKI_ADMIN:
                $this->addWikiAdministrator($project, $user);
                break;
        }
    }

    public function removeUser(Project $project, ProjectUGroup $ugroup, PFUser $user)
    {
        switch ($ugroup->getId()) {
            case ProjectUGroup::PROJECT_ADMIN:
                $this->removeProjectAdministrator($project, $user);
                break;
            case ProjectUGroup::WIKI_ADMIN:
                $this->removeWikiAdministrator($project, $user);
                break;
        }
    }

    private function addProjectAdministrator(Project $project, PFUser $user)
    {
        $this->ensureUserIsProjectMember($project, $user);

        $this->user_permissions_dao->addUserAsProjectAdmin($project->getID(), $user->getId());
        $this->event_manager->processEvent(new UserBecomesProjectAdmin($project, $user));
    }

    private function removeProjectAdministrator(Project $project, PFUser $user)
    {
        $this->user_permissions_dao->removeUserFromProjectAdmin($project->getID(), $user->getId());
        $this->event_manager->processEvent(new UserIsNoLongerProjectAdmin($project, $user));
    }

    private function addWikiAdministrator(Project $project, PFUser $user)
    {
        $this->ensureUserIsProjectMember($project, $user);

        $this->user_permissions_dao->addUserAsWikiAdmin($project->getID(), $user->getId());
        $this->event_manager->processEvent(new UserBecomesWikiAdmin($project, $user));
    }

    private function removeWikiAdministrator(Project $project, PFUser $user)
    {
        $this->user_permissions_dao->removeUserFromWikiAdmin($project->getID(), $user->getId());
        $this->event_manager->processEvent(new UserIsNoLongerWikiAdmin($project, $user));
    }

    private function ensureUserIsProjectMember(Project $project, PFUser $user)
    {
        if (! $user->isMember($project->getID())) {
            account_add_user_to_group($project->getID(), $user->getUserName());
            $this->ugroup_binding->reloadUgroupBindingInProject($project);
        }
    }
}
