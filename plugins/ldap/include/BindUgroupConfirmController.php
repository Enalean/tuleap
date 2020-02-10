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

namespace Tuleap\LDAP;

use HTTPRequest;
use LDAP_GroupManager;
use LDAP_UserGroupManager;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use UGroupManager;
use UserHelper;
use UserManager;

class BindUgroupConfirmController implements DispatchableWithRequest
{
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var LDAP_UserGroupManager
     */
    private $ldap_user_group_manager;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var UserHelper
     */
    private $user_helper;

    public function __construct(UGroupManager $ugroup_manager, LDAP_UserGroupManager $ldap_user_group_manager, UserManager $user_manager, UserHelper $user_helper)
    {
        $this->ugroup_manager          = $ugroup_manager;
        $this->ldap_user_group_manager = $ldap_user_group_manager;
        $this->user_manager            = $user_manager;
        $this->user_helper             = $user_helper;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array       $variables
     * @return void
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $ugroup_id = $request->getValidated('ugroup_id', 'uint', 0);
        if (! $ugroup_id) {
            $layout->send400JSONErrors('The ugroup ID is missing');
            return;
        }
        $ugroup = $this->ugroup_manager->getById($ugroup_id);

        if (! $request->getCurrentUser()->isAdmin($ugroup->getProjectId())) {
            $layout->sendStatusCode(403);
            return;
        }

        $bind_option = LDAP_GroupManager::BIND_OPTION;
        if ($request->get('preserve_members')) {
            $bind_option = LDAP_GroupManager::PRESERVE_MEMBERS_OPTION;
        }
        $bind_with_ugroup = $request->get('bind_with_group');

        $this->ldap_user_group_manager->setGroupName($bind_with_ugroup);
        $this->ldap_user_group_manager->setId($ugroup->getId());
        $this->ldap_user_group_manager->setProjectId($ugroup->getProjectId());

        if (! $this->ldap_user_group_manager->getGroupDn()) {
            $layout->send400JSONErrors(
                $GLOBALS['Language']->getText('project_ugroup_binding', 'ldap_group_error', $bind_with_ugroup)
            );
            return;
        }

        $to_remove = array();
        foreach ($this->ldap_user_group_manager->getUsersToBeRemoved($bind_option) as $user_id) {
            $user = $this->user_manager->getUserById($user_id);
            if ($user) {
                $to_remove[] = array(
                    'display_name' => $this->user_helper->getDisplayNameFromUser($user),
                    'has_avatar'   => $user->hasAvatar(),
                    'avatar_url'   => $user->getAvatarUrl()
                );
            }
        }

        $to_add = array();
        foreach ($this->ldap_user_group_manager->getUsersToBeAdded($bind_option) as $user_id) {
            $user = $this->user_manager->getUserById($user_id);
            if ($user) {
                $to_add[] = [
                    'display_name' => $this->user_helper->getDisplayNameFromUser($user),
                    'has_avatar'   => $user->hasAvatar(),
                    'avatar_url'   => $user->getAvatarUrl()
                ];
            }
        }

        $layout->sendJSON(
            array(
                'users_to_remove' => $to_remove,
                'users_to_add'    => $to_add,
                'nb_not_impacted' => count($this->ldap_user_group_manager->getUsersNotImpacted($bind_option)),
            )
        );
    }
}
