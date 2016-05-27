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

use Tuleap\PullRequest\Exception\PullRequestCannotBeAbandoned;
use Tuleap\PullRequest\Exception\PullRequestCannotBeMerged;
use Git_Command_Exception;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FileSystemIterator;
use GitRepository;
use GitRepositoryFactory;
use ForgeConfig;

class PullRequestCloser
{
    /**
     * @var Factory
     */
    private $pull_request_factory;

    public function __construct(Factory $factory)
    {
        $this->pull_request_factory = $factory;
    }

    public function abandon(PullRequest $pull_request)
    {
        $status = $pull_request->getStatus();

        if ($status === PullRequest::STATUS_ABANDONED) {
            return true;
        }

        if ($status === PullRequest::STATUS_MERGED) {
            throw new PullRequestCannotBeAbandoned('This pull request has already been merged, it can no longer be abandoned');
        }
        return $this->pull_request_factory->markAsAbandoned($pull_request);
    }

    public function abandonFromSourceBranch(GitRepository $repository, $branch_name)
    {
        $prs = $this->pull_request_factory->getOpenedBySourceBranch($repository, $branch_name);
        foreach ($prs as $pr) {
            $this->abandon($pr);
        }
    }

    public function markManuallyMerged(
        GitRepositoryFactory $git_repository_factory,
        GitRepository $dest_repository,
        $dest_branch_name,
        $new_rev
    ) {
        $prs = $this->pull_request_factory->getOpenedByDestinationBranch($dest_repository, $dest_branch_name);

        foreach ($prs as $pr) {
            $repository = $git_repository_factory->getRepositoryById($pr->getRepoDestId());
            $git_exec = new GitExec($repository->getFullPath(), $repository->getFullPath());
            if ($git_exec->isAncestor($new_rev, $pr->getSha1Src())) {
                $this->pull_request_factory->markAsMerged($pr);
            }
        }
    }

    public function fastForwardMerge(
        GitRepository $repository_src,
        GitRepository $repository_dest,
        PullRequest $pull_request
    ) {
        $status = $pull_request->getStatus();

        if ($status === PullRequest::STATUS_MERGED) {
            return true;
        }

        if ($status === PullRequest::STATUS_ABANDONED) {
            throw new PullRequestCannotBeMerged(
                'This pull request has already been abandoned, it can no longer be merged'
            );
        }

        $temporary_name       = $this->getUniqueRandomDirectory();
        $executor             = new GitExec($temporary_name);

        try {
            $executor->init();
            $executor->fetchNoHistory($repository_dest->getFullPath(), $pull_request->getBranchDest());
            $executor->fetch($repository_src->getFullPath(), $pull_request->getBranchSrc());
            $executor->fastForwardMerge($pull_request->getSha1Src());
            $executor->push(escapeshellarg('file://' . $repository_dest->getFullPath()) . ' HEAD:' . escapeshellarg($pull_request->getBranchDest()));
        } catch (Git_Command_Exception $exception) {
            throw new PullRequestCannotBeMerged(
                'This Pull Request cannot be merged. It seems that the attempted merge is not fast-forward'
            );
        }

        $this->cleanTemporaryRepository($temporary_name);

        return $this->pull_request_factory->markAsMerged($pull_request);
    }

    private function getUniqueRandomDirectory()
    {
        $tmp = ForgeConfig::get('codendi_cache_dir');

        return exec("mktemp -d -p $tmp pr_XXXXXX");
    }

    private function cleanTemporaryRepository($temporary_name)
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $temporary_name,
                FileSystemIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $filename => $file_information) {
            if ($file_information->isDir()) {
                rmdir($filename);
            } else {
                unlink($filename);
            }
        }

        rmdir($temporary_name);
    }
}
