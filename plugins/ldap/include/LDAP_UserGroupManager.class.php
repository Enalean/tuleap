<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2008
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'LDAP_UserGroupDao.class.php';
require_once 'LDAP_GroupManager.class.php';

/**
 * Manage interaction between an LDAP group and Codendi user_group.
 */
class LDAP_UserGroupManager 
extends LDAP_GroupManager
{
    /**
     * Add (by name) new users into a user group.
     * 
     * @param Array   $userList List of user identifier (e.g. ldap login)
     * 
     * @return void
     */
    public function addListOfUsersToGroup($userList) 
    {
        $ldapUserManager = new LDAP_UserManager($this->getLdap());        
        $userIds = $ldapUserManager->getUserIdsFromUserList($userList);
        foreach ($userIds as $userId) {
            $this->addUserToGroup($this->id, $userId);
        }
    }

    /**
     * Add user to a user group
     *
     * @param Integer $ugroupId Codendi Group ID
     * @param Integer $userId   User ID
     *
     * @return Boolean
     */
    protected function addUserToGroup($ugroupId, $userId)
    {
        return $this->getDao()->addUserToGroup($ugroupId, $userId);
    }

    /**
     * Remove user from a user group
     *
     * @param Integer $ugroupId Codendi Group ID
     * @param Integer $userId   User ID
     *
     * @return Boolean
     */
    protected function removeUserFromGroup($ugroupId, $userId)
    {
        return $this->getDao()->removeUserFromGroup($ugroupId, $userId);
    }

    /**
     * Get the codendi user_group members ids
     *
     * @param Integer $ugroupId ID of user group
     *
     * @return Array
     */
    public function getDbGroupMembersIds($ugroupId)
    {
        return $this->getDao()->getMembersId($ugroupId);
    }

    /**
     * Retrieve usergroups having synchro_policy option as 'auto'
     *
     * @return DataAccessResult
     */
    public function getSynchronizedUgroups() {
        return $this->getDao()->getSynchronizedUgroups();
    }

    /**
     * Check if a given ugroup is synchronized with an ldap group
     *
     * @param Integer $ugroup_id User group id to check
     *
     * @return Boolean
     */
    public function isSynchronizedUgroup($ugroup_id) {
        return $this->getDao()->isSynchronizedUgroup($ugroup_id);
    }

    /**
     * Check if a given ugroup is preserving members
     *
     * @param Integer $ugroup_id User group id to check
     *
     * @return Boolean
     */
    public function isMembersPreserving($ugroup_id) {
        return $this->getDao()->isMembersPreserving($ugroup_id);
    }

    /**
     * Check if the update of members of an ugroup is allowed
     *
     * @param Integer $ugroup_id User group id
     *
     * @return Boolean
     */
    public function isMembersUpdateAllowed($ugroup_id) {
        return $this->getDao()->isMembersUpdateAllowed($ugroup_id);
    }

    /**
     * Return dao
     *
     * @return LDAP_UserGroupDao
     */
    function getDao() 
    {
        return new LDAP_UserGroupDao(CodendiDataAccess::instance());
    }

    /**
     * Synchronize the ugroups with the ldap ones
     *
     * @return void
     */
    function synchronizeUgroups() {
        $dar = $this->getSynchronizedUgroups();
        if ($dar && !$dar->isError() && $dar->rowCount() > 0) {
        foreach($dar as $row) {
                $this->setId($row['ugroup_id']);
                $this->setGroupDn($row['ldap_group_dn']);
                $isNightlySynchronized = 'auto';
                $displayFeedback       = false;
                $this->bindWithLdap($row['bind_option'], $isNightlySynchronized, $displayFeedback);
            }
        }
    }
}

?>