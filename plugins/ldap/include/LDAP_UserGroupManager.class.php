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

/**
 * Manage interaction between an LDAP group and Codendi user_group.
 */
class LDAP_UserGroupManager extends LDAP_GroupManager
{
    private $project_id;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var LDAP_UserGroupDao
     */
    private $dao;

    public function __construct(
        LDAP $ldap,
        LDAP_UserManager $ldap_user_manager,
        LDAP_UserGroupDao $dao,
        ProjectManager $project_manager,
        \Psr\Log\LoggerInterface $logger,
        \Tuleap\LDAP\GroupSyncNotificationsManager $notifications_manager
    ) {
        parent::__construct($ldap, $ldap_user_manager, $project_manager, $notifications_manager);

        $this->project_manager = $project_manager;
        $this->logger          = $logger;
        $this->dao             = $dao;
    }

    public function setProjectId($project_id)
    {
        $this->project_id = $project_id;
    }

    /**
     * Add (by name) new users into a user group.
     *
     * @param Array   $userList List of user identifier (e.g. ldap login)
     *
     * @return void
     */
    public function addListOfUsersToGroup($userList)
    {
        $ldapUserManager = new LDAP_UserManager($this->getLdap(), LDAP_UserSync::instance());
        $userIds = $ldapUserManager->getUserIdsFromUserList($userList);
        foreach ($userIds as $userId) {
            $this->addUserToGroup($this->id, $userId);
        }
    }

    /**
     * Add user to a user group
     *
     * @param int $ugroupId Codendi Group ID
     * @param int $userId User ID
     *
     * @return bool
     */
    protected function addUserToGroup($ugroupId, $userId)
    {
        return $this->getDao()->addUserToGroup($ugroupId, $userId);
    }

    /**
     * Remove user from a user group
     *
     * @param int $ugroupId Codendi Group ID
     * @param int $userId User ID
     *
     * @return bool
     */
    protected function removeUserFromGroup($ugroupId, $userId)
    {
        return $this->getDao()->removeUserFromGroup($ugroupId, $userId);
    }

    /**
     * Get the codendi user_group members ids
     *
     * @param int $ugroupId ID of user group
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
    public function getSynchronizedUgroups()
    {
        return $this->getDao()->getSynchronizedUgroups();
    }

    /**
     * Check if a given ugroup is synchronized with an ldap group
     *
     * @param int $ugroup_id User group id to check
     *
     * @return bool
     */
    public function isSynchronizedUgroup($ugroup_id)
    {
        return $this->getDao()->isSynchronizedUgroup($ugroup_id);
    }

    /**
     * Check if a given ugroup is preserving members
     *
     * @param int $ugroup_id User group id to check
     *
     * @return bool
     */
    public function isMembersPreserving($ugroup_id)
    {
        return $this->getDao()->isMembersPreserving($ugroup_id);
    }

    /**
     * Check if the update of members of an ugroup is allowed
     *
     * @param int $ugroup_id User group id
     *
     * @return bool
     */
    public function isMembersUpdateAllowed($ugroup_id)
    {
        return $this->getDao()->isMembersUpdateAllowed($ugroup_id);
    }

    /**
     * Return dao
     *
     * @return LDAP_UserGroupDao
     */
    public function getDao()
    {
        return $this->dao;
    }

    /**
     * Synchronize the ugroups with the ldap ones
     *
     * @return void
     */
    public function synchronizeUgroups()
    {
        $dar = $this->getSynchronizedUgroups();
        if ($dar && !$dar->isError() && $dar->rowCount() > 0) {
            foreach ($dar as $row) {
                $this->setId($row['ugroup_id']);
                $this->setGroupDn($row['ldap_group_dn']);
                $this->setProjectId($row['project_id']);
                $isNightlySynchronized = self::AUTO_SYNCHRONIZATION;
                $displayFeedback       = false;
                $this->bindWithLdap($row['bind_option'], $isNightlySynchronized, $displayFeedback);
            }
        }
    }

    protected function diffDbAndDirectory($option)
    {
        parent::diffDbAndDirectory($option);

        $project = $this->project_manager->getProject($this->project_id);
        if (! $project->isPublic()) {
            $this->logger->warning("The synchronisation for ugroup #$this->id is done in a private projects.\n" .
                'Non project members will not be added or will be removed of this ugroup.');

            $project_member_ids = $project->getMembersId();

            $this->clearUsersToAddInPrivateProjectContext($project_member_ids);
            $this->addInUsersToRemoveUsersThatAreNotProjectMember($project_member_ids);
        }
    }

    private function clearUsersToAddInPrivateProjectContext(array $project_member_ids)
    {
        foreach ($this->usersToAdd as $key => $user_id) {
            if (! in_array($user_id, $project_member_ids)) {
                $this->logger->warning("The user #$user_id will not be added to this ugroup because he/she is not project member.");

                unset($this->usersToAdd[$key]);
            }
        }
    }

    private function addInUsersToRemoveUsersThatAreNotProjectMember(array $project_member_ids)
    {
        $ugroup_members = $this->getDbGroupMembersIds($this->id);
        foreach ($ugroup_members as $user_id) {
            if (! in_array($user_id, $project_member_ids)) {
                $this->logger->warning("The user #$user_id will be removed of this ugroup because he/she is not project member.");
                $this->usersToRemove[$user_id] = $user_id;
                $this->removeUserFromNotImpactedAsItWillBeDeleted($user_id);
            }
        }
    }

    private function removeUserFromNotImpactedAsItWillBeDeleted($user_id)
    {
        if (isset($this->usersNotImpacted[$user_id])) {
            unset($this->usersNotImpacted[$user_id]);
        }
    }
}
