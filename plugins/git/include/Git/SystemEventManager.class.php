<?php
/**
 * Copyright Enalean (c) 2011, 2012, 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

require_once 'common/system_event/SystemEventManager.class.php';

/**
 * I'm responsible to create system events with the right parameters
 */
class Git_SystemEventManager {
    /** @var SystemEventManager */
    private $system_event_manager;

    public function __construct(SystemEventManager $system_event_manager) {
        $this->system_event_manager = $system_event_manager;
    }

    public function queueRepositoryUpdate(GitRepository $repository) {
        if ($repository->getBackend() instanceof Git_Backend_Gitolite) {
            $this->system_event_manager->createEvent(
                SystemEvent_GIT_REPO_UPDATE::NAME,
                $repository->getId(),
                SystemEvent::PRIORITY_HIGH,
                SystemEvent::OWNER_APP
            );
        }
    }

    public function queueRepositoryDeletion(GitRepository $repository) {
        $this->system_event_manager->createEvent(
            SystemEvent_GIT_REPO_DELETE::NAME,
            $repository->getProjectId() . SystemEvent::PARAMETER_SEPARATOR . $repository->getId(),
            SystemEvent::PRIORITY_MEDIUM,
             $repository->getBackend() instanceof Git_Backend_Gitolite ? SystemEvent::OWNER_APP : SystemEvent::OWNER_ROOT
        );
    }

    public function queueRemoteProjectDeletion(GitRepository $repository) {
        $this->system_event_manager->createEvent(
            SystemEvent_GIT_GERRIT_PROJECT_DELETE::NAME,
            $repository->getId(). SystemEvent::PARAMETER_SEPARATOR . $repository->getRemoteServerId(),
            SystemEvent::PRIORITY_HIGH,
            SystemEvent::OWNER_APP
        );
    }

    public function queueRemoteProjectReadOnly(GitRepository $repository) {
        $this->system_event_manager->createEvent(
            SystemEvent_GIT_GERRIT_PROJECT_READONLY::NAME,
            $repository->getId(). SystemEvent::PARAMETER_SEPARATOR . $repository->getRemoteServerId(),
            SystemEvent::PRIORITY_HIGH,
            SystemEvent::OWNER_APP
        );
    }

    public function queueRepositoryFork(GitRepository $old_repository, GitRepository $new_repository) {
        $this->system_event_manager->createEvent(
            SystemEvent_GIT_REPO_FORK::NAME,
            $old_repository->getId() . SystemEvent::PARAMETER_SEPARATOR . $new_repository->getId(),
            SystemEvent::PRIORITY_MEDIUM,
            SystemEvent::OWNER_APP
        );
    }

    public function queueGitShellAccess(GitRepository $repository, $type) {
        $this->system_event_manager->createEvent(
            SystemEvent_GIT_REPO_ACCESS::NAME,
            $repository->getId() . SystemEvent::PARAMETER_SEPARATOR . $type,
            SystemEvent::PRIORITY_HIGH
        );
    }

    public function queueMigrateToGerrit(GitRepository $repository, $remote_server_id, $migrate_access_right) {
        $this->system_event_manager->createEvent(
            SystemEvent_GIT_GERRIT_MIGRATION::NAME,
            $repository->getId() . SystemEvent::PARAMETER_SEPARATOR . $remote_server_id . SystemEvent::PARAMETER_SEPARATOR . $migrate_access_right,
            SystemEvent::PRIORITY_HIGH,
            SystemEvent::OWNER_APP
        );
    }

    public function queueGerritReplicationKeyUpdate(Git_RemoteServer_GerritServer $server) {
        $this->system_event_manager->createEvent(
            SystemEvent_GIT_GERRIT_ADMIN_KEY_DUMP::NAME,
            $server->getId(),
            SystemEvent::PRIORITY_HIGH,
            SystemEvent::OWNER_APP
        );
    }

    public function isRepositoryMigrationToGerritOnGoing(GitRepository $repository) {
        return $this->system_event_manager->isThereAnEventAlreadyOnGoing(SystemEvent_GIT_GERRIT_MIGRATION::NAME, $repository->getId());
    }

    public function isProjectDeletionOnGerritOnGoing(GitRepository $repository) {
        return $this->system_event_manager->isThereAnEventAlreadyOnGoing(SystemEvent_GIT_GERRIT_PROJECT_DELETE::NAME, $repository->getId());
    }

    public function isProjectSetReadOnlyOnGerritOnGoing(GitRepository $repository) {
        return $this->system_event_manager->isThereAnEventAlreadyOnGoing(SystemEvent_GIT_GERRIT_PROJECT_READONLY::NAME, $repository->getId());
    }

    public function getTypes() {
        return array(
            SystemEvent_GIT_REPO_UPDATE::NAME,
            SystemEvent_GIT_REPO_DELETE::NAME,
            SystemEvent_GIT_REPO_FORK::NAME,
            SystemEvent_GIT_REPO_ACCESS::NAME,
            SystemEvent_GIT_GERRIT_MIGRATION::NAME,
            SystemEvent_GIT_GERRIT_ADMIN_KEY_DUMP::NAME,
            SystemEvent_GIT_GERRIT_PROJECT_DELETE::NAME,
            SystemEvent_GIT_GERRIT_PROJECT_READONLY::NAME
        );
    }
}

?>
