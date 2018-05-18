<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

/**
 * Manage interaction between an LDAP group and Project members
 */
class LDAP_ProjectGroupManager extends LDAP_GroupManager
{

    /**
     * @var LDAP_ProjectGroupDao
     */
    private $dao;
    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function __construct(LDAP $ldap, LDAP_UserManager $ldap_user_manager, LDAP_ProjectGroupDao $dao, ProjectManager $project_manager)
    {
        parent::__construct($ldap, $ldap_user_manager);

        $this->dao             = $dao;
        $this->project_manager = $project_manager;
    }

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
        $this->logInProjectHistory($groupId, $userId);

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
        $project = $this->project_manager->getProject($groupId);
        return $project->getMembersId();
    }

    /**
     * Get DataAccessObject
     *
     * @return LDAP_ProjectGroupDao
     */
    protected function getDao()
    {
        return $this->dao;
    }

    public function isProjectBindingSynchronized($project_id)
    {
        return $this->getDao()->isProjectBindingSynchronized($project_id);
    }

    public function doesProjectBindingKeepUsers($project_id)
    {
        return $this->getDao()->doesProjectBindingKeepUsers($project_id);
    }

    private function getSynchronizedProjects()
    {
        return $this->getDao()->getSynchronizedProjects();
    }

    public function synchronize()
    {
        foreach ($this->getSynchronizedProjects() as $row) {
            $dn = $row['ldap_group_dn'];

            $this->setId($row['group_id']);
            $this->setGroupDn($dn);

            $is_nightly_synchronized = self::AUTO_SYNCHRONIZATION;
            $display_feedback        = false;

            if ($this->doesLdapGroupExist($dn)) {
                $this->bindWithLdap($row['bind_option'], $is_nightly_synchronized, $display_feedback);
                $this->project_manager->clearProjectFromCache($row['group_id']);
            }
        }
    }

    private function doesLdapGroupExist($dn)
    {
        return $this->getLdap()->searchDn($dn);
    }

    private function logInProjectHistory($project_id, $user_id)
    {
        $project_log_dao = new ProjectHistoryDao();
        $user            = UserManager::instance()->getUserById($user_id);

        if ($user->isAdmin($project_id)) {
            $project_log_dao->groupAddHistory(
                'project_admins_daily_synchronization_user_not_removed',
                $user->getUnixName(),
                $this->id,
                array()
            );
        }

        return true;
    }
}
