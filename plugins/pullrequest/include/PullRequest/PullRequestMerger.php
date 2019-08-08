<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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
use Git_Command_Exception;
use Tuleap\PullRequest\Exception\PullRequestCannotBeMerged;
use System_Command;
use Tuleap\PullRequest\MergeSetting\MergeSettingRetriever;

class PullRequestMerger
{
    public const GIT_MERGE_CONFLICT_MARKER = '+<<<<<<<';
    public const MERGE_TEMPORARY_SUBFOLDER = 'tuleap-pr';
    /**
     * @var MergeSettingRetriever
     */
    private $merge_setting_retriever;

    public function __construct(MergeSettingRetriever $merge_setting_retriever)
    {
        $this->merge_setting_retriever = $merge_setting_retriever;
    }

    /**
     * @throws PullRequestCannotBeMerged
     */
    public function doMergeIntoDestination(PullRequest $pull_request, GitRepository $repository_dest, PFUser $user)
    {
        if ((int) $pull_request->getRepoDestId() !== (int) $repository_dest->getId()) {
            throw new \LogicException('Destination repository ID does not match the one of the PR');
        }

        try {
            $temp_working_dir = $this->getUniqueRandomDirectory();
        } catch (\System_Command_CommandException $exception) {
            throw new PullRequestCannotBeMerged('Temporary directory to merge the pull request can not be created');
        }
        $executor = new GitExec($temp_working_dir);

        $merge_setting = $this->merge_setting_retriever->getMergeSettingForRepository($repository_dest);

        try {
            $executor->sharedCloneAndCheckout($repository_dest->getFullPath(), $pull_request->getBranchDest());
            if ($merge_setting->isMergeCommitAllowed()) {
                $executor->merge($pull_request->getSha1Src(), $user);
            } else {
                $executor->fastForwardMergeOnly($pull_request->getSha1Src());
            }
            $executor->push(escapeshellarg('gitolite@gl-adm:' . $repository_dest->getPath()) . ' HEAD:' . escapeshellarg($pull_request->getBranchDest()));
        } catch (Git_Command_Exception $exception) {
            $exception_message = $exception->getMessage();
            throw new PullRequestCannotBeMerged(
                "This Pull Request cannot be merged: $exception_message"
            );
        } finally {
            $this->cleanTemporaryRepository($temp_working_dir);
        }
    }


    public function detectMergeabilityStatus(
        GitExec $git_exec_destination,
        $merge_revision,
        $destination_revision
    ) {
        try {
            if ($this->isFastForwardable($git_exec_destination, $merge_revision, $destination_revision)) {
                return PullRequest::FASTFORWARD_MERGE;
            }
            return $this->detectMergeConflict($git_exec_destination, $merge_revision, $destination_revision);
        } catch (Git_Command_Exception $e) {
            return PullRequest::UNKNOWN_MERGE;
        }
    }

    private function isFastForwardable(GitExec $git_exec, $merge_revision, $destination_revision)
    {
        return $git_exec->isAncestor($merge_revision, $destination_revision);
    }

    private function detectMergeConflict(GitExec $git_exec, $merge_revision, $destination_revision)
    {
        $merge_bases = $git_exec->mergeBase($merge_revision, $destination_revision);

        if (empty($merge_bases)) {
            return PullRequest::UNKNOWN_MERGE;
        }

        $merge_result_lines = $git_exec->mergeTree($merge_bases[0], $destination_revision, $merge_revision);

        foreach ($merge_result_lines as $merge_result_line) {
            if (strpos($merge_result_line, self::GIT_MERGE_CONFLICT_MARKER) === 0) {
                return PullRequest::CONFLICT_MERGE;
            }
        }

        return PullRequest::NO_FASTFORWARD_MERGE;
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
