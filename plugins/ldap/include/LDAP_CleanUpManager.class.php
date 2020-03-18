<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2014. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Project\UserRemover;

class LDAP_CleanUpManager
{

    /*
     * @var retentionPeriod int
     *
     * retentionPeriod correspond to days elapsed before deleting suspended user
     */
    private $retentionPeriod;

    /**
     * @var UserRemover
     */
    private $user_remover;

    public function __construct(UserRemover $user_remover, $period)
    {
        $this->retentionPeriod = $period;
        $this->user_remover    = $user_remover;
    }

    /**
     *
     * Clean all suspeneded users whose retention period is outdated
     *
     */
    public function cleanAll()
    {
        $directoryCleanUpDao = $this->getLDAPDirectoryCleanUpDao();
        $suspendedUsersList  = $directoryCleanUpDao->getAllSuspendedUsers($_SERVER['REQUEST_TIME']);
        if (!$suspendedUsersList) {
            $this->getBackendLogger()->error("[LDAP Clean Up] Error when getting all suspended users");
        } else {
            foreach ($suspendedUsersList as $currentUserData) {
                $user = $this->getUserManager()->getUserById($currentUserData['user_id']);
                $this->deleteSuspendedUser($user);
            }
        }
    }

    /**
     * Add forecast date for suspended user
     *
     * @param PFUser user
     *
     */
    public function addUserDeletionForecastDate(PFUser $user)
    {
        $directoryCleanUpDao = $this->getLDAPDirectoryCleanUpDao();
        $deletionDate        = $_SERVER['REQUEST_TIME'] + ($this->retentionPeriod * 24 * 60 * 60);
        $creationResult      = $directoryCleanUpDao->createForecastDeletionDate($user->getId(), $deletionDate);
        if (!$creationResult) {
            $this->getBackendLogger()->error("[LDAP Clean Up] Error when adding forecast deletion date to user " . $user->getUserName());
        } else {
            $this->getBackendLogger()->info("[LDAP Clean Up] Forecast deletion date added to user  " . $user->getUserName());
        }
    }

    /**
     * Delete suspended user
     *
     * @param PFUser user
     *
     */
    private function deleteSuspendedUser(PFUser $user)
    {
        if ($user->getStatus() == 'S') {
            $user->setStatus('D');
            $deletionResult = $this->getUserManager()->updateDb($user);
            if (!$deletionResult) {
                $this->getBackendLogger()->error("[LDAP Clean Up] Error when deleting user " . $user->getUserName());
            } else {
                $directoryCleanUpDao = $this->getLDAPDirectoryCleanUpDao();
                $resetResult         = $directoryCleanUpDao->resetForecastDeletionDate($user->getId());
                if (!$resetResult) {
                    $this->getBackendLogger()->warning("[LDAP Clean Up] Unable to reset forecast deletion date for user " . $user->getUserName());
                }
                $this->removeUserFromProjects($user);
                $this->getBackendLogger()->info("[LDAP Clean Up] user " . $user->getUserName() . "  have been deleted");
            }
        }
    }

    /**
     * Remove user from all projects
     *
     * @param PFUser user
     *
     */
    private function removeUserFromProjects(PFUser $user)
    {
        $project_list = $this->getUserProjects($user);
        foreach ($project_list as $project) {
            $removal_result = $this->user_remover->removeUserFromProject($project->getID(), $user->getId());

            if (! $removal_result) {
                $this->getBackendLogger()->warning('[LDAP Clean Up] Unable to remove user ' . $user->getUserName() . " from project " . $project->getID());
            }
        }
    }

    /**
     * Retrieve a collection of active projects of a given user
     *
     * @param Integer userId
     *
     * @return Array
     */
    private function getUserProjects($userId)
    {
        $pm = $this->_getProjectManager();
        return $pm->getAllProjectsForUserIncludingTheOnesSheDoesNotHaveAccessTo($userId);
    }

    /**
     * Wrapper
     *
     * @return ProjectManager
     */
    private function _getProjectManager()
    {
        return ProjectManager::instance();
    }

    /**
     * Wrapper for UserManager object
     *
     * @return UserManager
     */
    private function getUserManager()
    {
        return UserManager::instance();
    }

    /**
     * Wrapper for LDAP_DirectoryCleanUpDao object
     *
     * @return LDAP_DirectoryCleanUpDao
     */
    private function getLDAPDirectoryCleanUpDao()
    {
        return new LDAP_DirectoryCleanUpDao(CodendiDataAccess::instance());
    }

    /**
     * Wrapper for BackendLogger object
     */
    private function getBackendLogger(): \Psr\Log\LoggerInterface
    {
         return BackendLogger::getDefaultLogger();
    }
}
