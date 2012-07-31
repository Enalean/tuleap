<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'GitRepositoryFactory.class.php';
require_once 'common/system_event/SystemEventManager.class.php';
require_once 'common/project/ProjectManager.class.php';

/**
 * This class is responsible of management of several repositories.
 *
 * It works in close cooperation with GitRepositoryFactory (to instanciate repo)
 */
class GitRepositoryManager {

    /**
     * @var GitRepositoryFactory
     */
    private $repository_factory;

    /**
     * @var SystemEventManager
     */
    private $system_event_manager;

    /**
     * @param GitRepositoryFactory $repository_factory
     * @param SystemEventManager   $system_event_manager
     */
    public function __construct(GitRepositoryFactory $repository_factory, SystemEventManager $system_event_manager) {
        $this->repository_factory   = $repository_factory;
        $this->system_event_manager = $system_event_manager;
    }

    /**
     * Delete all project repositories (on project deletion).
     *
     * @param Project $project
     */
    public function deleteProjectRepositories(Project $project) {
        $repositories = $this->repository_factory->getAllRepositories($project);
        foreach ($repositories as $repository) {
            $repository->forceMarkAsDeleted();
            $this->system_event_manager->createEvent(
                'GIT_REPO_DELETE',
                 $project->getID().SystemEvent::PARAMETER_SEPARATOR.$repository->getId(),
                 SystemEvent::PRIORITY_MEDIUM
            );
        }
    }

    /**
     * Return true if proposed name already exists as a repository path
     *
     * @param Project $project
     * @param String  $name
     *
     * @return Boolean
     */
    public function isRepositoryNameAlreadyUsed(GitRepository $new_repository) {
        $repositories = $this->repository_factory->getAllRepositories($new_repository->getProject());
        foreach ($repositories as $existing_repo) {
            $new_repo_path      = $new_repository->getPathWithoutLazyLoading();
            $existing_repo_path = $existing_repo->getPathWithoutLazyLoading();
            if ($this->nameIsSubPathOfExistingRepository($existing_repo_path, $new_repo_path)) {
                return true;
            }
            if ($this->nameAlreadyExistsAsPath($existing_repo_path, $new_repo_path)) {
                return true;
            }
        }
    }

    private function nameIsSubPathOfExistingRepository($repository_path, $new_path) {
        $repo_path_without_dot_git = $this->stripFinalDotGit($repository_path);
        if (strpos($new_path, "$repo_path_without_dot_git/") === 0) {
            return true;
        }
        return false;
    }

    private function nameAlreadyExistsAsPath($repository_path, $new_path) {
        $new_path = $this->stripFinalDotGit($new_path);
        if (strpos($repository_path, "$new_path/") === 0) {
            return true;
        }
        return false;
    }

    private function stripFinalDotGit($path) {
        return substr($path, 0, strrpos($path, '.git'));
    }
}

?>
