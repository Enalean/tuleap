<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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
use Psr\Log\LoggerInterface;
use Tuleap\NeverThrow\Fault;
use Tuleap\Process\ProcessExecutionFailure;
use Tuleap\Process\ProcessFactory;
use Tuleap\PullRequest\Exception\PullRequestCannotBeMerged;
use Tuleap\PullRequest\MergeSetting\MergeSettingRetriever;

readonly class PullRequestMerger
{
    public const string MERGE_TEMPORARY_SUBFOLDER = 'tuleap-pr';


    public function __construct(
        private MergeSettingRetriever $merge_setting_retriever,
        private ProcessFactory $process_factory,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws PullRequestCannotBeMerged
     */
    public function doMergeIntoDestination(PullRequest $pull_request, GitRepository $repository_dest, PFUser $user): void
    {
        if ((int) $pull_request->getRepoDestId() !== $repository_dest->getId()) {
            throw new \LogicException('Destination repository ID does not match the one of the PR');
        }

        $temp_working_dir = $this->getUniqueRandomDirectory();
        $executor         = new GitExec($temp_working_dir);

        $merge_setting = $this->merge_setting_retriever->getMergeSettingForRepository($repository_dest);

        try {
            $executor->sharedCloneAndCheckout($repository_dest->getFullPath(), $pull_request->getBranchDest());
            if ($merge_setting->isMergeCommitAllowed()) {
                $executor->merge($pull_request->getSha1Src(), $user);
            } else {
                $executor->fastForwardMergeOnly($pull_request->getSha1Src());
            }

            $push_merge_process = $this->process_factory->buildProcess([
                'sudo',
                '-u',
                'gitolite',
                'DISPLAY_ERRORS=true',
                __DIR__ . '/../../bin/push-pr-merge.php',
                $temp_working_dir,
                $repository_dest->getFullPath(),
                $pull_request->getBranchDest(),
            ]);

            $push_merge_process->run()
                ->mapErr(
                    function (ProcessExecutionFailure $execution_failure): never {
                        Fault::writeToLogger($execution_failure->fault, $this->logger);
                        throw new PullRequestCannotBeMerged('Failure to push the merge result');
                    }
                );
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
        $destination_revision,
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

    private function isFastForwardable(GitExec $git_exec, $merge_revision, $destination_revision): bool
    {
        return $git_exec->isAncestor($merge_revision, $destination_revision);
    }

    private function detectMergeConflict(GitExec $git_exec, $merge_revision, $destination_revision)
    {
        $merge_bases = $git_exec->mergeBase($merge_revision, $destination_revision);

        if (empty($merge_bases)) {
            return PullRequest::UNKNOWN_MERGE;
        }

        $merge_result_conflict_marker = $git_exec->searchMergeConflictSymbolInMergeTree($merge_bases[0], $destination_revision, $merge_revision);

        if (count($merge_result_conflict_marker) > 0) {
            return PullRequest::CONFLICT_MERGE;
        }

        return PullRequest::NO_FASTFORWARD_MERGE;
    }

    private function getUniqueRandomDirectory(): string
    {
        $parent_tmp = \ForgeConfig::get('tmp_dir') . DIRECTORY_SEPARATOR . self::MERGE_TEMPORARY_SUBFOLDER;

        is_dir($parent_tmp) || mkdir($parent_tmp, 0750, true);
        chgrp($parent_tmp, 'gitolite');

        $random_directory = $parent_tmp . DIRECTORY_SEPARATOR . 'pr_' . bin2hex(random_bytes(8));
        \Psl\Filesystem\create_directory($random_directory, 0750);
        chgrp($random_directory, 'gitolite');

        return $random_directory;
    }

    private function cleanTemporaryRepository(string $temporary_name): void
    {
        $path = \Psl\Filesystem\canonicalize($temporary_name);
        if ($path === null) {
            return;
        }
        $check_path = strpos($path, self::MERGE_TEMPORARY_SUBFOLDER);
        if ($check_path !== false) {
            \Tuleap\File\DirectoryRemover::deleteDirectory($path);
        }
    }
}
