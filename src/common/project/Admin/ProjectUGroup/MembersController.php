<?php
/**
 * Copyright (c) Enalean, 2011 - 2017. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Codendi_Request;
use Feedback;
use Project;
use ProjectUGroup;
use Tuleap\Project\UserPermissionsDao;
use UGroupBinding;
use UserManager;

class MembersController
{
    /**
     * @var Codendi_Request
     */
    private $request;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var UserPermissionsDao
     */
    private $user_permissions_dao;
    /**
     * @var UGroupBinding
     */
    private $ugroup_binding;

    public function __construct(
        Codendi_Request $request,
        UserManager $user_manager,
        UserPermissionsDao $user_permissions_dao,
        UGroupBinding $ugroup_binding
    ) {
        $this->request              = $request;
        $this->user_manager         = $user_manager;
        $this->user_permissions_dao = $user_permissions_dao;
        $this->ugroup_binding       = $ugroup_binding;
    }

    public function editMembers(Project $project, ProjectUGroup $ugroup)
    {
        $is_update_allowed = ! $ugroup->isBound();
        if (! $is_update_allowed) {
            return;
        }

        $this->removeUserFromUGroup($project, $ugroup);
        $this->addUserToUGroup($project, $ugroup);
    }

    private function addUserToUGroup(Project $project, ProjectUGroup $ugroup)
    {
        $add_user_name = $this->request->get('add_user_name');
        if (! $add_user_name) {
            return;
        }
        $user = $this->user_manager->findUser($add_user_name);
        if (! $user) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('include_account', 'user_not_exist')
            );
        }
        if ($ugroup->isStatic()) {
            ugroup_add_user_to_ugroup($project->getID(), $ugroup->getId(), $user->getId());

            return;
        }

        if (! $user->isMember($project->getID())) {
            account_add_user_to_group($project->getID(), $user->getUserName());
            $this->ugroup_binding->reloadUgroupBindingInProject($project);
        }
        $this->user_permissions_dao->addUserAsProjectAdmin($project->getID(), $user->getId());
    }

    /**
     * @param Project       $project
     * @param ProjectUGroup $ugroup
     */
    private function removeUserFromUGroup(Project $project, ProjectUGroup $ugroup)
    {
        $user_id = $this->request->get('remove_user');
        if (! $user_id) {
            return;
        }

        $user = $this->user_manager->getUserById($user_id);
        if (! $user) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('include_account', 'user_not_exist')
            );
        }

        if ($ugroup->isStatic()) {
            ugroup_remove_user_from_ugroup($project->getID(), $ugroup->getId(), $user_id);

            return;
        }

        $this->user_permissions_dao->removeUserFromProjectAdmin($project->getID(), $user->getId());
    }
}
