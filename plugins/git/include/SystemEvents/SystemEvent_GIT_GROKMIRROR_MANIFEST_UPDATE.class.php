<?php
/**
 * Copyright (c) Enalean, 2014 - 2015. All Rights Reserved.
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

class SystemEvent_GIT_GROKMIRROR_MANIFEST_UPDATE extends SystemEvent
{
    public const NAME = 'GIT_GROKMIRROR_MANIFEST_UPDATE';

    /** @var GitRepositoryFactory */
    protected $repository_factory;

    /** @var Git_Mirror_ManifestManager */
    protected $manifest_manager;

    public function injectDependencies(GitRepositoryFactory $repository_factory, Git_Mirror_ManifestManager $manifest_manager)
    {
        $this->repository_factory = $repository_factory;
        $this->manifest_manager   = $manifest_manager;
    }

    private function getRepositoryIdFromParameters()
    {
        $parameters = $this->getParametersAsArray();
        return intval($parameters[0]);
    }

    protected function getRepositoryFromParameters()
    {
        return $this->repository_factory->getRepositoryById($this->getRepositoryIdFromParameters());
    }

    public function process()
    {
        $repository = $this->getRepositoryFromParameters();
        if (! $repository) {
            $this->warning('Unable to find repository, perhaps it was deleted in the mean time?');
            return false;
        }

        $this->manifest_manager->triggerUpdate($repository);

        $this->done();
    }

    public function verbalizeParameters($with_link)
    {
        if ($with_link) {
            $repository = $this->getRepositoryFromParameters();
            if ($repository) {
                return '<a href="/plugins/git/?action=repo_management&group_id=' . $repository->getProjectId() . '&repo_id=' . $repository->getId() . '">' . $repository->getName() . '</a>';
            }
        }
        return $this->getRepositoryIdFromParameters();
    }
}
