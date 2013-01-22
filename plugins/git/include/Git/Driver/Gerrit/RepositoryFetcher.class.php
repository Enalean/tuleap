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

    /**
     * @var BackendLogger
     */
    private $logger;

    public function __construct(GitRepositoryFactory $repository_factory, BackendLogger $logger) {
        $this->repository_factory = $repository_factory;
        $this->logger = $logger;
    }

    /**
     * Update all repositories from their remote references
     */
    public function process() {
        $repositories = $this->repository_factory->getActiveRepositoriesWithRemoteServersForAllProjects();
        if (count($repositories)) {
            $this->logger->info('Gerrit fetch all repositories...');
            foreach ($repositories as $repository) {
                try {
                    $this->updateRepositoryFromRemote($repository);
                } catch (Git_Command_Exception $exception) {
                    $this->logger->error('Error raised while updating repository ' . $repository->getFullPath() . ' (id: ' . $repository->getId() . ')', $exception);
                } catch (Exception $exception) {
                    $this->logger->error('Unknown error', $exception);
                }
            }
            $this->logger->info('Gerrit fetch all repositories done');
        }
    }

    private function updateRepositoryFromRemote(GitRepository $repository) {
        $this->logger->info('Gerrit fetch '.$repository->getFullPath().' (id: '.$repository->getId().')');
        $git_exec = $this->getGitExecForRepository($repository->getFullPath(), Git_Driver_Gerrit_ProjectCreator::GERRIT_REMOTE_NAME);
        $git_exec->fetch();
        $remote_heads = $git_exec->lsRemoteHeads();
        foreach ($remote_heads as $remote_head) {
            $this->alignLocalHeadWithRemoteOne($git_exec, $remote_head);
        }
    }

    private function alignLocalHeadWithRemoteOne(Git_Driver_Gerrit_ExecFetch $git_exec, $remote_head) {
        $matches = array();
        //extract the branch name
        preg_match('/refs\/heads\/(.*)/', $remote_head, $matches);
        if(! isset($matches[1])) {
            return;
        }

        $branch_name = $matches[1];
        //updating the local repository with the remote content
        $git_exec->updateRef($branch_name);
    }

    protected function getGitExecForRepository($repository_path, $remote_name) {
        return new Git_Driver_Gerrit_ExecFetch($repository_path, $remote_name);
    }
}

?>
