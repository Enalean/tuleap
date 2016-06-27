<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
use ForgeConfig;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FileSystemIterator;
use System_Command;

class PullRequestMerger
{

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
        $temp_working_dir = $this->getUniqueRandomDirectory();
        $executor         = new GitExec($temp_working_dir);

        try {
            $this->tryMerge($pull_request, $executor, $user);
            $executor->push(escapeshellarg('file://' . $repository_dest->getFullPath()) . ' HEAD:' . escapeshellarg($pull_request->getBranchDest()));
        } catch (Git_Command_Exception $exception) {
            $this->cleanTemporaryRepository($temp_working_dir);
            $exception_message = $exception->getMessage();
            throw new PullRequestCannotBeMerged(
                "This Pull Request cannot be merged: $exception_message"
            );
        }
        $this->cleanTemporaryRepository($temp_working_dir);
    }


    public function detectMergeabilityStatus(GitExec $git_exec, PullRequest $pull_request)
    {
        try {
            if ($this->isFastForwardable($git_exec, $pull_request)) {
                $merge_status = PullRequest::FASTFORWARD_MERGE;
            } else {
                $merge_status = $this->detectMergeConflict($pull_request);
            }
        } catch (Git_Command_Exception $e) {
            $merge_status = PullRequest::UNKNOWN_MERGE;
        }
        return $merge_status;
    }

    private function tryMerge($pull_request, $executor, $user)
    {
        $repository_src  = $this->git_repository_factory->getRepositoryById($pull_request->getRepositoryId());
        $repository_dest = $this->git_repository_factory->getRepositoryById($pull_request->getRepoDestId());

        $executor->init();
        $executor->fetchAndCheckout($repository_dest->getFullPath(), $pull_request->getBranchDest());
        $executor->fetch($repository_src->getFullPath(), $pull_request->getBranchSrc());
        return $executor->merge($pull_request->getSha1Src(), $user);
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

    private function detectMergeConflict(PullRequest $pull_request)
    {
        $temporary_name = $this->getUniqueRandomDirectory();
        $executor       = new GitExec($temporary_name);
        $user           = new PFUser(array('realname' => 'Tuleap Merge Resolver',
                                           'email'    => 'merger@tuleap.net'));

        try {
            $merge_result = $this->tryMerge($pull_request, $executor, $user);
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

    private function getUniqueRandomDirectory()
    {
        $tmp = ForgeConfig::get('codendi_cache_dir');

        return exec("mktemp -d -p $tmp pr_XXXXXX");
    }

    private function cleanTemporaryRepository($temporary_name)
    {
        $path       = realpath($temporary_name);
        $check_path = strpos($path, ForgeConfig::get('codendi_cache_dir'));
        if ($check_path !== false) {
            $cmd = new System_Command();
            $cmd->exec('rm -rf ' . escapeshellarg($path));
        }
    }
}
