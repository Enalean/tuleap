<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'AdminDelegation_UserServiceLogDao.class.php';
require_once 'AdminDelegation_UserServiceDao.class.php';
require_once 'AdminDelegation_Service.class.php';

class AdminDelegation_UserServiceManager {
    protected $_userServiceDao;
    protected $_userServiceLogDao;
    
    public function getGrantedUsers() {
        $usDao = $this->_getUserServiceDao();
        return $usDao->searchAllUsers();
    }
    
    public function getGrantedUsersForService($serviceId) {
        $usDao = $this->_getUserServiceDao();
        return $usDao->searchAllUserService($serviceId);
    }

    public function addUserService($user, $service) {
        $usDao = $this->_getUserServiceDao();
        if ($usDao->addUserService($user->getId(), $service)) {
            $uslDao = $this->_getUserServiceLogDao();
            $uslDao->addLog('grant', $service, $user->getId(), $_SERVER['REQUEST_TIME']);
            return true;
        }
        return false;
    }

    public function removeUserService($user, $service) {
        $usDao = $this->_getUserServiceDao();
        if ($usDao->removeUserService($user->getId(), $service)) {
            $uslDao = $this->_getUserServiceLogDao();
            $uslDao->addLog('revoke', $service, $user->getId(), $_SERVER['REQUEST_TIME']);
            return true;
        }
        return false;
    }

    public function removeUser($user) {
        $ret   = true;
        $usDao = $this->_getUserServiceDao();
        $darUserServices = $usDao->searchUser($user->getId());
        
        $usDao->removeUser($user->getId());
        
        $uslDao = $this->_getUserServiceLogDao();
        foreach ($darUserServices as $row) {
            $uslDao->addLog('revoke', $row['service_id'], $user->getId(), $_SERVER['REQUEST_TIME']);
        }
        return $ret;
    }
    
    public function isUserGranted($user) {
        $usDao  = $this->_getUserServiceDao();
        return $usDao->isUserGranted($user->getId());
    }

    public function isUserGrantedForService($user, $service) {
        $usDao  = $this->_getUserServiceDao();
        return $usDao->isUserGrantedForService($user->getId(), $service);
    }

    protected function _getUserServiceLogDao() {
        if (!$this->_userServiceLogDao) {
            $this->_userServiceLogDao = new AdminDelegation_UserServiceLogDao(CodendiDataAccess::instance());
        }
        return $this->_userServiceLogDao;
    }

    protected function _getUserServiceDao() {
        if (!$this->_userServiceDao) {
            $this->_userServiceDao = new AdminDelegation_UserServiceDao(CodendiDataAccess::instance());
        }
        return $this->_userServiceDao;
    }
}

?>