<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\AdminDelegation;

use AdminDelegation_Service;
use AdminDelegation_UserServiceManager;
use UserManager;

class AdminDelegationBuilder
{
    /**
     * @var AdminDelegation_UserServiceManager
     */
    private $user_delegation_manager;
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(AdminDelegation_UserServiceManager $user_delegation_manager, UserManager $user_manager)
    {
        $this->user_delegation_manager = $user_delegation_manager;
        $this->user_manager            = $user_manager;
    }

    public function buildUsers()
    {
        $permissisons = $this->retrievePermissionsByUsers();
        $user_list    = $this->enhancePermissionsWithUserInformations($permissisons);

        return $user_list;
    }

    private function retrievePermissionsByUsers()
    {
        $users = [];

        foreach ($this->user_delegation_manager->getGrantedUsers() as $user) {
            $users[$user['user_id']][] = AdminDelegation_Service::getLabel(
                $user['service_id']
            );
        }

        return $users;
    }

    private function enhancePermissionsWithUserInformations(array $permissions_by_users)
    {
        $user_list   = [];

        foreach ($permissions_by_users as $key => $permissions) {
            $user = $this->user_manager->getUserById($key);

            $user_list[] = [
                'user_id'          => $user->getId(),
                'has_avatar'       => $user->hasAvatar(),
                'user_avatar'      => $user->getAvatarUrl(),
                'user_name'        => $user->getName(),
                'user_permissions' => implode(', ', $permissions)
            ];
        }

        return $user_list;
    }

    public function buildServices()
    {
        $service_list = [];

        foreach (AdminDelegation_Service::getAllServices() as $service) {
            $built_service = [
                'id' => $service,
                'label' => AdminDelegation_Service::getLabel($service),
            ];
            $service_list[] = $built_service;
        }

        return $service_list;
    }
}
