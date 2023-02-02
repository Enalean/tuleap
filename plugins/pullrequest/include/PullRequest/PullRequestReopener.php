<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\PullRequest;

use Git_Command_Exception;
use GitRepositoryFactory;
use PFUser;
use Tuleap\PullRequest\Exception\PullRequestCannotBeReopen;
use Tuleap\PullRequest\Exception\UnknownBranchNameException;
use Tuleap\PullRequest\GitReference\GitReferenceNotFound;
use Tuleap\PullRequest\Timeline\TimelineEventCreator;

class PullRequestReopener
{
    public function __construct(
        private Dao $pull_request_dao,
        private GitRepositoryFactory $git_repository_factory,
        private GitExecFactory $git_exec_factory,
        private PullRequestUpdater $pull_request_updater,
        private TimelineEventCreator $timeline_event_creator,
    ) {
    }

    /**
     * @throws PullRequestCannotBeReopen
     */
    public function reopen(PullRequest $pull_request, PFUser $user_reopening_the_pr): void
    {
        $status = $pull_request->getStatus();

        if ($status === PullRequest::STATUS_REVIEW) {
            throw new PullRequestCannotBeReopen('This pull request is already open.');
        }

        if ($status === PullRequest::STATUS_MERGED) {
            throw new PullRequestCannotBeReopen('This pull request has already been merged, it cannot be reopened.');
        }

        $source_repository = $this->git_repository_factory->getRepositoryById($pull_request->getRepositoryId());
        if ($source_repository === null) {
            throw new PullRequestCannotBeReopen('Source repository cannot be found.');
        }
        $git_exec_for_source = $this->git_exec_factory->getGitExec($source_repository);

        $destination_repository = $this->git_repository_factory->getRepositoryById($pull_request->getRepoDestId());
        if ($destination_repository === null) {
            throw new PullRequestCannotBeReopen('Destination repository cannot be found.');
        }
        $git_exec_for_destination = $this->git_exec_factory->getGitExec($destination_repository);

        try {
            $new_source_rev = $git_exec_for_source->getBranchSha1($pull_request->getBranchSrc());
            $git_exec_for_destination->getBranchSha1($pull_request->getBranchDest());

            if ($new_source_rev !== $pull_request->getSha1Src()) {
                $this->pull_request_updater->updatePullRequestWithNewSourceRev(
                    $pull_request,
                    $user_reopening_the_pr,
                    $source_repository,
                    $new_source_rev,
                );
            }

            $this->pull_request_dao->reopen($pull_request->getId());
            $this->timeline_event_creator->storeReopenEvent(
                $pull_request,
                $user_reopening_the_pr,
            );
        } catch (
            UnknownBranchNameException |
            GitReferenceNotFound |
            Git_Command_Exception $exception
        ) {
            throw new PullRequestCannotBeReopen($exception->getMessage());
        }
    }
}
