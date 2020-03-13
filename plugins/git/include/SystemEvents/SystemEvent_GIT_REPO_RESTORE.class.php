<?php
/**
 * Copyright (c) STMicroelectronics, 2015. All Rights Reserved.
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
 * Description of SystemEvent_GIT_REPO_DELETE
 */
class SystemEvent_GIT_REPO_RESTORE extends SystemEvent
{
    public const NAME = 'GIT_REPO_RESTORE';

    /** @var GitRepositoryFactory */
    private $repository_factory;

    /** @var Git_SystemEventManager */
    private $system_event_manager;

    public function injectDependencies(
        GitRepositoryFactory $repository_factory,
        Git_SystemEventManager $system_event_manager
    ) {
        $this->repository_factory   = $repository_factory;
        $this->system_event_manager = $system_event_manager;
    }

    public function process()
    {
        $parameters    = $this->getParametersAsArray();
        $repository_id = 0;

        if (!empty($parameters[0])) {
            $repository_id = (int) $parameters[0];
        } else {
            $this->error('Missing argument repository id');
            return false;
        }

        $repository         = $this->repository_factory->getDeletedRepository($repository_id);
        $active_repository  = $this->repository_factory->getRepositoryByPath($repository->getProject()->getId(), $repository->getPath());

        if ($active_repository instanceof GitRepository) {
            $this->error('Repository with the same name already exsit');
            return false;
        }

        if (!$repository->getBackend()->restoreArchivedRepository($repository)) {
            $this->error('Unable to restore repository : ' . $repository->getName());
            return false;
        }

        $repository->getBackend()->updateRepoConf($repository);
        $this->system_event_manager->queueGrokMirrorManifest($repository);

        $this->done();
    }

    public function verbalizeParameters($with_link)
    {
        $repository = $this->getRepositoryFromParameters();
        if ($repository !== null) {
            return '<a href="/plugins/git/?action=repo_management&group_id=' . $repository->getProjectId() . '&repo_id=' . $repository->getId() . '">' . $repository->getName() . '</a>';
        }
        return '';
    }

    private function getRepositoryFromParameters()
    {
        return $this->repository_factory->getRepositoryById($this->getRepositoryIdFromParameters());
    }

    private function getRepositoryIdFromParameters()
    {
        $parameters = $this->getParametersAsArray();
        return intval($parameters[0]);
    }
}
