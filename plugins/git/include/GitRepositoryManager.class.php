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
     * Create a new GitRepository through its backend
     *
     * @param  GitRepository $repository
     * @throws Exception
     */
    public function create(GitRepository $repository) {
        if (!$repository->isNameValid($repository->getName())) {
            throw new Exception($GLOBALS['Language']->getText('plugin_git', 'actions_input_format_error', array($repository->getBackend()->getAllowedCharsInNamePattern(), GitDao::REPO_NAME_MAX_LENGTH)));
        }
        $this->assertRepositoryNameNotAlreadyUsed($repository);
        $repository->getBackend()->createReference($repository);
    }

    /**
     * Fork a repository
     *
     * @param GitRepository $repository The repo to fork
     * @param Project       $to_project The project to create the repo in
     * @param User          $user       The user who does the fork (she will own the clone)
     * @param String        $namespace  The namespace to put the repo in (might be emtpy)
     * @param String        $scope      Either GitRepository::REPO_SCOPE_INDIVIDUAL or GitRepository::REPO_SCOPE_PROJECT
     */
    public function fork(GitRepository $repository, Project $to_project, User $user, $namespace, $scope) {
        $clone = clone $repository;
        $clone->setProject($to_project);
        $clone->setCreator($user);
        $clone->setParent($repository);
        $clone->setNamespace($namespace);
        $clone->setId(null);
        $path = unixPathJoin(array($to_project->getUnixName(), $namespace, $repository->getName())).'.git';
        $clone->setPath($path);
        $clone->setScope($scope);

        $this->assertRepositoryNameNotAlreadyUsed($clone);
        $repository->getBackend()->fork($repository, $clone);
    }

    private function assertRepositoryNameNotAlreadyUsed(GitRepository $repository) {
        if ($this->isRepositoryNameAlreadyUsed($repository)) {
            throw new Exception($GLOBALS['Language']->getText('plugin_git', 'actions_create_repo_exists', array($repository->getName())));
        }
    }

    /**
     * For several repositories at once
     *
     * @param array         $repositories Array of GitRepositories to fork
     * @param Project       $to_project   The project to create the repo in
     * @param User          $user         The user who does the fork (she will own the clone)
     * @param String        $namespace    The namespace to put the repo in (might be emtpy)
     * @param String        $scope        Either GitRepository::REPO_SCOPE_INDIVIDUAL or GitRepository::REPO_SCOPE_PROJECT
     *
     * @return Boolean
     *
     * @throws Exception
     */
    public function forkRepositories(array $repositories, Project $to_project, User $user, $namespace, $scope) {
        $repos = array_filter($repositories);
        if (count($repos) > 0 && $this->isNamespaceValid($repos[0], $namespace)) {
            return $this->forkAllRepositories($repos, $user, $namespace, $scope, $to_project);
        }
        throw new Exception($GLOBALS['Language']->getText('plugin_git', 'actions_no_repository_forked'));
    }

    private function isNamespaceValid(GitRepository $repository, $namespace) {
        if ($namespace) {
            $ns_chunk = explode('/', $namespace);
            foreach ($ns_chunk as $chunk) {
                if (!$repository->isNameValid($chunk)) {
                    throw new Exception($GLOBALS['Language']->getText('plugin_git', 'fork_repository_invalid_namespace'));
                }
            }
        }
        return true;
    }

    private function forkAllRepositories(array $repos, User $user, $namespace, $scope, Project $project) {
        $forked = false;
        foreach ($repos as $repo) {
            try {
                if ($repo->userCanRead($user)) {
                    $this->fork($repo, $project, $user, $namespace, $scope);
                    $forked = true;
                }
            } catch (GitRepositoryAlreadyExistsException $e) {
                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('plugin_git', 'fork_repository_exists', array($repo->getName())));
            } catch (Exception $e) {
                $GLOBALS['Response']->addFeedback('warning', 'Got an unexpected error while forking ' . $repo->getName() . ': ' . $e->getMessage());
            }
        }
        return $forked;
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
            if ($new_repo_path == $existing_repo_path) {
                return true;
            }
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
