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

    public function injectDependencies(GitRepositoryFactory $repository_factory) {
        $this->repository_factory = $repository_factory;
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
        $repository->getBackend()->updateRepoConf($repository);
        $this->done();
    }

    public function verbalizeParameters($with_link) {
        $repository = $this->getRepositoryFromParameters();
        if ($repository) {
            if ($with_link) {
                return '<a href="/plugins/git/?action=repo_management&group_id='.$repository->getProjectId().'&repo_id='.$repository->getId().'">'.$repository->getName().'</a>';
            } else {
                return $repository->getId();
            }
        } else {
            return $this->getRepositoryIdFromParameters();
        }
    }


}

?>
