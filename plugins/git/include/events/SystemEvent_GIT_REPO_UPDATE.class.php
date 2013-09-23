<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once 'common/system_event/SystemEvent.class.php';

class SystemEvent_GIT_REPO_UPDATE extends SystemEvent {
    const NAME = 'GIT_REPO_UPDATE';

    /** @var GitRepositoryFactory */
    private $repository_factory;

    /** @var SystemEventDao */
    private $system_event_dao;

    public function injectDependencies(GitRepositoryFactory $repository_factory, SystemEventDao $system_event_dao) {
        $this->repository_factory = $repository_factory;
        $this->system_event_dao   = $system_event_dao;
    }

    public static function queueInSystemEventManager(SystemEventManager $system_event_manager, GitRepository $repository) {
        $system_event_manager->createEvent(
            self::NAME,
            $repository->getId(),
            SystemEvent::PRIORITY_HIGH,
            SystemEvent::OWNER_APP
        );
    }

    private function getRepositoryIdFromParameters() {
        $parameters = $this->getParametersAsArray();
        return intval($parameters[0]);
    }

    private function getRepositoryFromParameters() {
        return $this->repository_factory->getRepositoryById($this->getRepositoryIdFromParameters());
    }

    public function process() {
        $repository = $this->getRepositoryFromParameters();
        if (! $repository) {
            $this->warning('Unable to find repository, perhaps it was deleted in the mean time?');
            return;
        }

        $events_to_repos = $this->getAllEvents();
        $event_ids       = $this->getOtherEventIds($events_to_repos);

        $this->system_event_dao->markAsRunning($event_ids);

        $repositories = $this->getOneRepoPerProject($events_to_repos);

        $repository->getBackend()->updateAllRepoConf($repositories);
        $this->system_event_dao->markAsDone($event_ids);

        $this->done();
    }

    public function verbalizeParameters($with_link) {
        if ($with_link) {
            $repository = $this->getRepositoryFromParameters();
            if ($repository) {
                return '<a href="/plugins/git/?action=repo_management&group_id='.$repository->getProjectId().'&repo_id='.$repository->getId().'">'.$repository->getName().'</a>';
            }
        }
        return $this->getRepositoryIdFromParameters();
    }

    private function getAllEvents() {
        $event_repositories = array();

        $events = $this->system_event_dao->searchNewGitRepoUpdateEvents();

        if(! $events) {
            return $event_repositories;
        }

        foreach ($events as $event) {
            $repository_id = $this->getRepositoryIdFromEvent($event);

            if ($repository_id && ! in_array($repository_id, $event_repositories)) {
                $event_repositories[$event['id']] = $repository_id;
            }
        }

        return $event_repositories;
    }

    private function getRepositoryIdFromEvent($event) {
        $parameters = explode('::', $event['parameters']);

        return $parameters[0];
    }

    private function getOtherEventIds($events_to_repos) {
        return array_diff(array_keys($events_to_repos), array($this->id));
    }

    /**
     * @param array $repositories
     * @return GitRepository[]
     */
    private function getOneRepoPerProject($repositories) {
        $skipped_repositories = array();
        $used_repositories    = array();

        foreach ($repositories as $repo_id) {
            if (in_array($repo_id, $skipped_repositories)) {
                continue;
            }

            $repository = $this->repository_factory->getRepositoryById($repo_id);

            $other_repositories = $repository->getBackend()
                ->searchOtherRepositoriesInSameProjectFromRepositoryList($repository, $repositories);

            $skipped_repositories = array_merge($skipped_repositories, $other_repositories);

            $used_repositories[] = $repository;
        }

        return $used_repositories;
    }
}
?>
