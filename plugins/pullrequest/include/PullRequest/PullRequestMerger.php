<?php
/**
 * Copyright (c) Enalean, 2016-2017. All Rights Reserved.
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

namespace Tuleap\PullRequest;

use PFUser;
use GitRepository;
use GitRepositoryFactory;
use Git_Command_Exception;
use Tuleap\PullRequest\Exception\PullRequestCannotBeMerged;
use System_Command;

class PullRequestMerger
{

    const MERGE_TEMPORARY_SUBFOLDER = 'tuleap-pr';

    /**
     * @var GitRepositoryFactory
     */
    private $git_repository_factory;


    public function __construct(
        GitRepositoryFactory $git_repository_factory
    )
    {
        $this->git_repository_factory = $git_repository_factory;
    }

    public function doMergeIntoDestination(PullRequest $pull_request, GitRepository $repository_dest, PFUser $user)
    {
        try {
            $temp_working_dir = $this->getUniqueRandomDirectory();
        } catch (\System_Command_CommandException $exception) {
            throw new PullRequestCannotBeMerged('Temporary directory to merge the pull request can not be created');
        }
        $executor         = new GitExec($temp_working_dir);

        try {
            $this->tryMerge($pull_request, $pull_request->getSha1Src(), $executor, $user);
            $executor->push(escapeshellarg('gitolite@gl-adm:' . $repository_dest->getPath()) . ' HEAD:' . escapeshellarg($pull_request->getBranchDest()));
        } catch (Git_Command_Exception $exception) {
            $this->cleanTemporaryRepository($temp_working_dir);
            $exception_message = $exception->getMessage();
            throw new PullRequestCannotBeMerged(
                "This Pull Request cannot be merged: $exception_message"
            );
        }
        $this->cleanTemporaryRepository($temp_working_dir);
    }


    public function detectMergeabilityStatus(GitExec $git_exec, PullRequest $pull_request, $merge_rev, GitRepository $repository)
    {
        try {
            if ($this->isFastForwardable($git_exec, $pull_request)) {
                $merge_status = PullRequest::FASTFORWARD_MERGE;
            } else {
                $merge_status = $this->detectMergeConflict($pull_request, $merge_rev, $repository);
            }
        } catch (Git_Command_Exception $e) {
            $merge_status = PullRequest::UNKNOWN_MERGE;
        }
        return $merge_status;
    }

    private function tryMerge($pull_request, $merge_rev, $executor, $user)
    {
        $repository_src  = $this->git_repository_factory->getRepositoryById($pull_request->getRepositoryId());
        $repository_dest = $this->git_repository_factory->getRepositoryById($pull_request->getRepoDestId());

        $executor->cloneAndCheckout($repository_dest->getFullPath(), $pull_request->getBranchDest());
        $executor->fetch($repository_src->getFullPath(), $pull_request->getBranchSrc());
        return $executor->merge($merge_rev, $user);
    }

    private function isFastForwardable($git_exec, $pr)
    {
        if ($pr->getRepositoryId() != $pr->getRepoDestId()) {
            $git_exec->fetchRemote($pr->getRepoDestId());
            $src_ref  = 'refs/heads/' . $pr->getBranchSrc();
            $dest_ref = 'refs/remotes/' . $pr->getRepoDestId() . '/' . $pr->getBranchDest();
        } else {
            $src_ref  = $pr->getBranchSrc();
            $dest_ref = $pr->getBranchDest();
        }
        return $git_exec->isAncestor($src_ref, $dest_ref);
    }

    private function detectMergeConflict(PullRequest $pull_request, $merge_rev, GitRepository $repository)
    {
        $temporary_name = $this->getUniqueRandomDirectory($repository);
        $executor       = new GitExec($temporary_name);
        $user           = new PFUser(array('realname' => 'Tuleap Merge Resolver',
                                           'email'    => 'merger@tuleap.net'));

        try {
            $merge_result = $this->tryMerge($pull_request, $merge_rev, $executor, $user);
            if ($merge_result) {
                $merge_status = PullRequest::NO_FASTFORWARD_MERGE;
            } else {
                $merge_status = PullRequest::UNKNOWN_MERGE;
            }
        } catch (Git_Command_Exception $exception) {
            $merge_status = PullRequest::CONFLICT_MERGE;
        }

        $this->cleanTemporaryRepository($temporary_name);
        return $merge_status;
    }

    /**
     * @return string
     * @throws \System_Command_CommandException
     */
    private function getUniqueRandomDirectory()
    {
        $parent_tmp = \ForgeConfig::get('tmp_dir') . DIRECTORY_SEPARATOR . PullRequestMerger::MERGE_TEMPORARY_SUBFOLDER;

        is_dir($parent_tmp) || mkdir($parent_tmp, 0750, true);

        $cmd = new System_Command();
        $result_cmd = $cmd->exec('mktemp -d -p ' . escapeshellarg($parent_tmp) . ' pr_XXXXXX');
        return $result_cmd[0];
    }

    private function cleanTemporaryRepository($temporary_name)
    {
        $path       = realpath($temporary_name);
        $check_path = strpos($path, PullRequestMerger::MERGE_TEMPORARY_SUBFOLDER);
        if ($check_path !== false) {
            $cmd = new System_Command();
            $cmd->exec('rm -rf ' . escapeshellarg($path));
        }
    }
}
