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

require_once GIT_BASE_DIR.'/GitRepositoryFactory.class.php';
require_once 'ExecFetch.class.php';

class Git_Driver_Gerrit_RepositoryFetcher {
    /**
     * @var GitRepositoryFactory
     */
    private $repository_factory;

    public function __construct(GitRepositoryFactory $repository_factory) {
        $this->repository_factory = $repository_factory;
    }

    /**
     * Update all repositories from their remote references
     */
    public function process() {
        foreach ($this->repository_factory->getRepositoriesWithRemoteServersForAllProjects() as $repository) {
            /* @var $repository GitRepository */
            $git_exec = $this->getGitExecForRepository($repository->getFullPath(), Git_Driver_Gerrit_ProjectCreator::GERRIT_REMOTE_NAME);
            $git_exec->fetch();
            $remote_heads = $git_exec->lsRemoteHeads();
            foreach ($remote_heads as $remote_head) {
                $matches = array();
                //extract the branch name
                preg_match('/refs\/heads\/(.*)/', $remote_head, $matches);
                if(! isset($matches[1])) {
                    continue;
                }

                $branch_name =  $matches[1];
                //updating the local repository with the remote content
                //`cd $repository_path && git fetch $remote_name -q && git update-ref refs/heads/$branch_name refs/remotes/$remote_name/$branch_name`;
                $git_exec->updateRef($branch_name);
            }
        }
    }

    protected function getGitExecForRepository($repository_path, $remote_name) {
        return new Git_Driver_Gerrit_ExecFetch($repository_path, $remote_name);
    }
}

?>
