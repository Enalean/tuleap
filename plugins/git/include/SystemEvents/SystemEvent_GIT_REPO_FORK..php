<?php
/**
 * Copyright (c) Enalean, 2013-2018. All Rights Reserved.
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

use Tuleap\Git\PostInitGitRepositoryWithDataEvent;

class SystemEvent_GIT_REPO_FORK extends SystemEvent
{
    public const NAME =  'GIT_REPO_FORK';

    /** @var GitRepositoryFactory */
    private $repository_factory;

    public function injectDependencies(GitRepositoryFactory $repository_factory)
    {
        $this->repository_factory = $repository_factory;
    }

    private function getOldRepositoryIdFromParameters()
    {
        return intval($this->getParameter(0));
    }

    private function getNewRepositoryIdFromParameters()
    {
        return intval($this->getParameter(1));
    }

    private function getOldRepositoryFromParameters()
    {
        return $this->repository_factory->getRepositoryById($this->getOldRepositoryIdFromParameters());
    }

    private function getNewRepositoryFromParameters()
    {
        return $this->repository_factory->getRepositoryById($this->getNewRepositoryIdFromParameters());
    }

    public function process()
    {
        $old_repository = $this->getOldRepositoryFromParameters();
        $new_repository = $this->getNewRepositoryFromParameters();

        if (! $old_repository || ! $new_repository) {
            $this->warning('Unable to find repository, perhaps it was deleted in the mean time?');
            return;
        }

        $backend = $old_repository->getBackend();
        $backend->forkOnFilesystem($old_repository, $new_repository);
        $this->getEventManager()->processEvent(new PostInitGitRepositoryWithDataEvent($new_repository));
        $this->done();
    }

    public function verbalizeParameters($with_link)
    {
        $old_repository = $this->getOldRepositoryFromParameters();
        $new_repository = $this->getNewRepositoryFromParameters();
        if ($old_repository && $new_repository) {
            if ($with_link) {
                return 'Fork ' . $this->getLinkToRepositoryManagement($old_repository) . ' => ' . $this->getLinkToRepositoryManagement($new_repository);
            } else {
                return $old_repository->getId() . ' => ' . $new_repository->getId();
            }
        } else {
            return $this->getOldRepositoryIdFromParameters();
        }
    }

    private function getLinkToRepositoryManagement(GitRepository $repository)
    {
        $project = $repository->getProject();
        return '<a href="/plugins/git/?action=repo_management&group_id=' . $project->getId() . '&repo_id=' . $repository->getId() . '">' . $project->getUnixName() . '/' . $repository->getFullName() . '</a>';
    }
}
