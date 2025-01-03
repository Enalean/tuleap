<?php
/**
 * Copyright Enalean (c) 2011-Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
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

use Tuleap\Git\Branch\BranchName;
use Tuleap\Git\SystemEvents\ParseGitolite3Logs;
use Tuleap\Git\SystemEvents\ProjectIsSuspended;

/**
 * I'm responsible to create system events with the right parameters
 */
class Git_SystemEventManager
{
    public function __construct(private readonly SystemEventManager $system_event_manager)
    {
    }

    public function queueProjectsConfigurationUpdate(array $project_ids)
    {
        $this->system_event_manager->createEvent(
            SystemEvent_GIT_PROJECTS_UPDATE::NAME,
            implode(SystemEvent::PARAMETER_SEPARATOR, $project_ids),
            SystemEvent::PRIORITY_HIGH,
            SystemEvent::OWNER_APP
        );
    }

    public function queueRepositoryUpdate(GitRepository $repository, ?BranchName $default_branch = null): void
    {
        if (
            $repository->getBackend() instanceof Git_Backend_Gitolite &&
            ! $this->isRepositoryUpdateAlreadyQueued($repository)
        ) {
            $parameters = $repository->getId();
            if ($default_branch !== null) {
                $parameters .= SystemEvent::PARAMETER_SEPARATOR . $default_branch->name;
            }
            $this->system_event_manager->createEvent(
                SystemEvent_GIT_REPO_UPDATE::NAME,
                $parameters,
                SystemEvent::PRIORITY_HIGH,
                SystemEvent::OWNER_APP
            );
        }
    }

    public function queueRepositoryDeletion(GitRepository $repository)
    {
        if ($repository->getBackend() instanceof Git_Backend_Gitolite) {
            $this->system_event_manager->createEvent(
                SystemEvent_GIT_REPO_DELETE::NAME,
                $repository->getProjectId() . SystemEvent::PARAMETER_SEPARATOR . $repository->getId(),
                SystemEvent::PRIORITY_MEDIUM,
                SystemEvent::OWNER_APP
            );
        }
    }

    public function queueRemoteProjectDeletion(GitRepository $repository)
    {
        $this->system_event_manager->createEvent(
            SystemEvent_GIT_GERRIT_PROJECT_DELETE::NAME,
            $repository->getId() . SystemEvent::PARAMETER_SEPARATOR . $repository->getRemoteServerId(),
            SystemEvent::PRIORITY_HIGH,
            SystemEvent::OWNER_APP
        );
    }

    public function queueRemoteProjectReadOnly(GitRepository $repository)
    {
        $this->system_event_manager->createEvent(
            SystemEvent_GIT_GERRIT_PROJECT_READONLY::NAME,
            $repository->getId() . SystemEvent::PARAMETER_SEPARATOR . $repository->getRemoteServerId(),
            SystemEvent::PRIORITY_HIGH,
            SystemEvent::OWNER_APP
        );
    }

    public function queueRepositoryFork(GitRepository $old_repository, GitRepository $new_repository)
    {
        $this->system_event_manager->createEvent(
            SystemEvent_GIT_REPO_FORK::NAME,
            $old_repository->getId() . SystemEvent::PARAMETER_SEPARATOR . $new_repository->getId(),
            SystemEvent::PRIORITY_MEDIUM,
            SystemEvent::OWNER_APP
        );
    }

    public function queueMigrateToGerrit(GitRepository $repository, $remote_server_id, $gerrit_template_id, PFUser $requester)
    {
        $this->system_event_manager->createEvent(
            SystemEvent_GIT_GERRIT_MIGRATION::NAME,
            $repository->getId() . SystemEvent::PARAMETER_SEPARATOR . $remote_server_id . SystemEvent::PARAMETER_SEPARATOR . $gerrit_template_id . SystemEvent::PARAMETER_SEPARATOR . $requester->getId(),
            SystemEvent::PRIORITY_HIGH,
            SystemEvent::OWNER_APP
        );
    }

    public function queueGerritReplicationKeyUpdate(Git_RemoteServer_GerritServer $server)
    {
        $this->system_event_manager->createEvent(
            SystemEvent_GIT_GERRIT_ADMIN_KEY_DUMP::NAME,
            $server->getId(),
            SystemEvent::PRIORITY_HIGH,
            SystemEvent::OWNER_APP
        );
    }

    public function queueUserRenameUpdate($old_user_name, IHaveAnSSHKey $new_user)
    {
        $this->system_event_manager->createEvent(
            SystemEvent_GIT_USER_RENAME::NAME,
            $old_user_name . SystemEvent::PARAMETER_SEPARATOR . $new_user->getId(),
            SystemEvent::PRIORITY_HIGH,
            SystemEvent::OWNER_APP
        );
    }

    public function queueEditSSHKey($user_id, $original_keys)
    {
        $this->system_event_manager->createEvent(
            SystemEvent_GIT_EDIT_SSH_KEYS::NAME,
            $user_id . SystemEvent::PARAMETER_SEPARATOR . $original_keys,
            SystemEvent::PRIORITY_HIGH,
            SystemEvent::OWNER_APP
        );
    }

    public function queueDumpAllSSHKeys()
    {
        $this->system_event_manager->createEvent(
            SystemEvent_GIT_DUMP_ALL_SSH_KEYS::NAME,
            '',
            SystemEvent::PRIORITY_LOW,
            SystemEvent::OWNER_APP
        );
    }

    public function queueRepositoryRestore(GitRepository $repository)
    {
        if ($repository->getBackend() instanceof Git_Backend_Gitolite) {
            $this->system_event_manager->createEvent(
                SystemEvent_GIT_REPO_RESTORE::NAME,
                $repository->getId(),
                SystemEvent::PRIORITY_MEDIUM,
                SystemEvent::OWNER_APP
            );
        }
    }

    public function queueRegenerateGitoliteConfig($project_id)
    {
        $this->system_event_manager->createEvent(
            SystemEvent_GIT_REGENERATE_GITOLITE_CONFIG::NAME,
            $project_id,
            SystemEvent::PRIORITY_HIGH,
            SystemEvent::OWNER_APP
        );
    }

    public function queueProjectIsSuspended($project_id)
    {
        $this->system_event_manager->createEvent(
            ProjectIsSuspended::NAME,
            $project_id,
            SystemEvent::PRIORITY_HIGH,
            SystemEvent::OWNER_APP,
            ProjectIsSuspended::class
        );
    }

    public function isRepositoryUpdateAlreadyQueued(GitRepository $repository)
    {
        return $this->system_event_manager->areThereMultipleEventsQueuedMatchingFirstParameter(
            SystemEvent_GIT_REPO_UPDATE::NAME,
            $repository->getId()
        );
    }

    public function isRepositoryMigrationToGerritOnGoing(GitRepository $repository)
    {
        return $this->system_event_manager->isThereAnEventAlreadyOnGoingMatchingFirstParameter(SystemEvent_GIT_GERRIT_MIGRATION::NAME, $repository->getId());
    }

    public function isProjectDeletionOnGerritOnGoing(GitRepository $repository)
    {
        return $this->system_event_manager->isThereAnEventAlreadyOnGoingMatchingFirstParameter(SystemEvent_GIT_GERRIT_PROJECT_DELETE::NAME, $repository->getId());
    }

    public function isProjectSetReadOnlyOnGerritOnGoing(GitRepository $repository)
    {
        return $this->system_event_manager->isThereAnEventAlreadyOnGoingMatchingFirstParameter(SystemEvent_GIT_GERRIT_PROJECT_READONLY::NAME, $repository->getId());
    }

    public function getTypes()
    {
        return [
            SystemEvent_GIT_REPO_UPDATE::NAME,
            SystemEvent_GIT_REPO_DELETE::NAME,
            SystemEvent_GIT_REPO_FORK::NAME,
            SystemEvent_GIT_REPO_RESTORE::NAME,
            SystemEvent_GIT_GERRIT_MIGRATION::NAME,
            SystemEvent_GIT_GERRIT_ADMIN_KEY_DUMP::NAME,
            SystemEvent_GIT_GERRIT_PROJECT_DELETE::NAME,
            SystemEvent_GIT_GERRIT_PROJECT_READONLY::NAME,
            SystemEvent_GIT_USER_RENAME::NAME,
            SystemEvent_GIT_EDIT_SSH_KEYS::NAME,
            SystemEvent_GIT_DUMP_ALL_SSH_KEYS::NAME,
            SystemEvent_GIT_PROJECTS_UPDATE::NAME,
            SystemEvent_GIT_REGENERATE_GITOLITE_CONFIG::NAME,
            ProjectIsSuspended::NAME,
        ];
    }

    /**
     * Note: for a newly developed feature, it would be better to have a dedicated
     * queue for root event but
     * - This mean that for all new platforms there would be a new empty pane (git root
     *   events)
     * So it's better to make them run in the default queue like before
     *
     * @return string[]
     */
    public function getTypesForDefaultQueue(): array
    {
        return [
            ParseGitolite3Logs::NAME,
        ];
    }
}
