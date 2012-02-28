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
/**
 * Base class for fork commands
 *
 */
abstract class GitForkCommand {

    /**
     * Fork a list of repositories for the given user
     *
     * @param array $repos a list of GitRepository
     * @param User $user
     *
     * @return bool whether dofork was called once or not
     */
    public function fork($repos, User $user) {
        $forked = false;
        $repos  = $this->filterNullRepos($repos);
        foreach($repos as $repo) {
            try {
                if ($repo->userCanRead($user)) {
                    $this->dofork($repo, $user);
                    $forked = true;
                }
            } catch (Exception $e) {
                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('plugin_git', 'fork_repository_exists', array($repo->getName())));
            }
        }
        return $forked;
    }

    public function forkRepo(GitRepository $repo, $user, $namespace, $scope, $project) {
        $clone = clone $repo;
        $clone->setProject($project);
        $clone->setCreator($user);
        $clone->setParent($repo);
        $clone->setNamespace($namespace);
        $clone->setId(null);
        $path = unixPathJoin(array($project->getUnixName(), $namespace, $repo->getName())).'.git';
        $clone->setPath($path);
        $clone->setScope($scope);
        $repo->getBackend()->fork($repo, $clone);
    }

    /**
     * Remove null values in the given array
     *
     * @param array $repos the list of repositories we would filter
     *
     * return array the given parameter without null values
     */
    protected function filterNullRepos(array $repos) {
        return array_filter($repos);
    }

    /**
     * Fork repositoriy for a given user
     *
     * @param GitRepository $repo Git Repository to fork
     * @param User          $user User which ask for a fork
     *
     * @return null just do it
     */
    public abstract function dofork(GitRepository $repo, User $user);
}
?>