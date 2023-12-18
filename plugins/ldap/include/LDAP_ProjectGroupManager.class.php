<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

use Tuleap\LDAP\GroupSyncNotificationsManager;
use Tuleap\LDAP\LDAPSetOfUserIDsForDiff;
use Tuleap\LDAP\Project\AddProjectMembers;
use Tuleap\LDAP\ProjectGroupManagerRestrictedUserFilter;

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
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var ProjectGroupManagerRestrictedUserFilter
     */
    private $project_restricted_user_filter;

    public function __construct(
        LDAP $ldap,
        LDAP_UserManager $ldap_user_manager,
        LDAP_ProjectGroupDao $dao,
        ProjectManager $project_manager,
        UserManager $user_manager,
        GroupSyncNotificationsManager $notifications_manager,
        ProjectGroupManagerRestrictedUserFilter $project_restricted_user_filter,
        private readonly AddProjectMembers $add_project_members,
    ) {
        parent::__construct($ldap, $ldap_user_manager, $project_manager, $notifications_manager);

        $this->dao                            = $dao;
        $this->project_manager                = $project_manager;
        $this->user_manager                   = $user_manager;
        $this->project_restricted_user_filter = $project_restricted_user_filter;
    }

    /**
     * Add user to a project
     *
     * @param int $id Id of the project
     * @param int $userId User Id
     */
    protected function addUserToGroup($id, $userId): bool
    {
        $user = $this->user_manager->getUserById($userId);
        if ($user === null) {
            return false;
        }
        $project                  = $this->project_manager->getProject($this->id);
        $set_of_user_ids          = new LDAPSetOfUserIDsForDiff([$user->getId()], [], []);
        $filtered_set_of_user_ids = $this->project_restricted_user_filter->filter($project, $set_of_user_ids);
        if (empty($filtered_set_of_user_ids->getUserIDsToAdd())) {
            return false;
        }

        return $this->add_project_members->addProjectMember($project, $user);
    }

    /**
     * Remove user from a project
     *
     * @param int $id Id of the project
     * @param int $userId User ID
     *
     * @return bool
     */
    protected function removeUserFromGroup($id, $userId)
    {
        $this->logInProjectHistory($id, $userId);

        return $this->getDao()->removeUserFromGroup($id, $userId);
    }

    /**
     * Get project members user id
     *
     * @param int $groupId Id of project
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

    public function synchronize(): void
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

    protected function diffDbAndDirectory($option): void
    {
        parent::diffDbAndDirectory($option);

        $set_of_user_ids = new LDAPSetOfUserIDsForDiff(
            $this->usersToAdd,
            $this->usersToRemove,
            $this->usersNotImpacted
        );

        $project                  = $this->project_manager->getProject($this->id);
        $filtered_set_of_user_ids = $this->project_restricted_user_filter->filter($project, $set_of_user_ids);

        $this->usersToAdd       = $filtered_set_of_user_ids->getUserIDsToAdd();
        $this->usersToRemove    = $filtered_set_of_user_ids->getUserIDsToRemove();
        $this->usersNotImpacted = $filtered_set_of_user_ids->getUserIDsNotImpacted();
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
                $user->getUserName(),
                $this->id,
                []
            );
        }

        return true;
    }
}
