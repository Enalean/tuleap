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
use LDAP_ProjectGroupManager;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\Admin\MembershipDelegationDao;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use UserHelper;
use UserManager;
use Valid_GroupId;
use Valid_String;

class BindMembersConfirmController implements DispatchableWithRequest
{
    /**
     * @var LDAP_ProjectGroupManager
     */
    private $ldap_project_group_manager;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var UserHelper
     */
    private $user_helper;
    /**
     * @var MembershipDelegationDao
     */
    private $membership_delegation_dao;

    public function __construct(LDAP_ProjectGroupManager $ldap_project_group_manager, UserManager $user_manager, UserHelper $user_helper, MembershipDelegationDao $membership_delegation_dao)
    {
        $this->ldap_project_group_manager = $ldap_project_group_manager;
        $this->user_manager               = $user_manager;
        $this->user_helper                = $user_helper;
        $this->membership_delegation_dao  = $membership_delegation_dao;
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
        // Import very long user group may takes very long time.
        ini_set('max_execution_time', '0');

        // Get group id
        $vGroupId = new Valid_GroupId();
        $vGroupId->required();

        if (! $request->valid($vGroupId)) {
            $layout->send400JSONErrors("Group ID is missing");

            return;
        }
        $groupId = $request->get('group_id');

        // Must be a project admin
        $user = $request->getCurrentUser();
        if (! $user->isAdmin($groupId) && ! $this->membership_delegation_dao->doesUserHasMembershipDelegation($user->getId(), $groupId)) {
            throw new ForbiddenException();
        }

        // Check if user have choosen the preserve members option.
        $bind_option = LDAP_GroupManager::BIND_OPTION;
        if ($request->get('preserve_members')) {
            $bind_option = LDAP_GroupManager::PRESERVE_MEMBERS_OPTION;
        }

        // Get LDAP group name
        $vLdapGroup = new Valid_String('ldap_group');
        $vLdapGroup->required();

        if (! $request->valid($vLdapGroup)) {
            $layout->send400JSONErrors("Group ID is missing");
            return;
        }

        $this->ldap_project_group_manager->setId($groupId);
        $this->ldap_project_group_manager->setGroupName($request->get('ldap_group'));

        $group_dn = $this->ldap_project_group_manager->getGroupDn();
        if ($group_dn === false) {
            $layout->sendStatusCode(404);
            return;
        }

        $to_remove = array();
        foreach ($this->ldap_project_group_manager->getUsersToBeRemoved($bind_option) as $user_id) {
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
        foreach ($this->ldap_project_group_manager->getUsersToBeAdded($bind_option) as $user_id) {
            $user = $this->user_manager->getUserById($user_id);
            if ($user) {
                $to_add[] = array(
                    'display_name' => $this->user_helper->getDisplayNameFromUser($user),
                    'has_avatar'   => $user->hasAvatar(),
                    'avatar_url'   => $user->getAvatarUrl()
                );
            }
        }

        $layout->sendJSON(
            array(
                'users_to_remove' => $to_remove,
                'users_to_add'    => $to_add,
                'nb_not_impacted' => count($this->ldap_project_group_manager->getUsersNotImpacted($bind_option)),
            )
        );
    }
}
