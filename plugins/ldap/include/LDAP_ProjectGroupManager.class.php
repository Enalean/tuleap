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

require_once 'LDAP_ProjectGroupDao.class.php';
require_once 'LDAP_GroupManager.class.php';

/**
 * Manage interaction between an LDAP group and Project members
 */
class LDAP_ProjectGroupManager 
extends LDAP_GroupManager
{
    /**
     * Add user to a project
     *
     * @param Integer $groupId Id of the project
     * @param Integer $userId  User Id
     *
     * @return Boolean
     */
    protected function addUserToGroup($groupId, $userId)
    {
        $user = UserManager::instance()->getUserById($userId);
        return $this->getDao()->addUserToGroup($groupId, $user->getUserName());
    }

    /**
     * Remove user from a project
     *
     * @param Integer $groupId Id of the project
     * @param Integer $userId  User ID
     *
     * @return Boolean
     */
    protected function removeUserFromGroup($groupId, $userId)
    {
        return $this->getDao()->removeUserFromGroup($groupId, $userId);
    }

    /**
     * Get project members user id
     *
     * @param Integer $groupId Id of project
     *
     * @return Array
     */
    protected function getDbGroupMembersIds($groupId)
    {
        $project = ProjectManager::instance()->getProject($groupId);
        return $project->getMembersId();
    }

    /**
     * Get DataAccessObject
     *
     * @return LDAP_ProjectGroupDao
     */
    protected function getDao()
    {
        return new LDAP_ProjectGroupDao(CodendiDataAccess::instance());
    }

    public function isProjectBindingSynchronized($project_id)
    {
        return $this->getDao()->isProjectBindingSynchronized($project_id);
    }
}
