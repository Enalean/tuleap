<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
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

class AdminDelegation_UserServiceManager
{
    /**
     * @var AdminDelegation_UserServiceDao
     */
    private $user_service_dao;
    /**
     * @var AdminDelegation_UserServiceLogDao
     */
    private $user_service_log_dao;

    public function __construct(
        AdminDelegation_UserServiceDao $user_service_dao,
        AdminDelegation_UserServiceLogDao $user_service_log_dao
    ) {
        $this->user_service_dao     = $user_service_dao;
        $this->user_service_log_dao = $user_service_log_dao;
    }

    public function getGrantedUsers()
    {
        return $this->user_service_dao->searchAllUsers();
    }

    public function addUserService(PFUser $user, $service, $time)
    {
        if ($this->user_service_dao->addUserService($user->getId(), $service)) {
            $this->user_service_log_dao->addLog('grant', $service, $user->getId(), $time);
            return true;
        }
        return false;
    }

    public function removeUser(PFUser $user, $time)
    {
        $ret   = true;
        $darUserServices = $this->user_service_dao->searchUser($user->getId());
        $this->user_service_dao->removeUser($user->getId());

        foreach ($darUserServices as $row) {
            $this->user_service_log_dao->addLog('revoke', $row['service_id'], $user->getId(), $time);
        }
        return $ret;
    }

    public function isUserGrantedForService(PFUser $user, $service)
    {
        return $this->user_service_dao->isUserGrantedForService($user->getId(), $service);
    }
}
