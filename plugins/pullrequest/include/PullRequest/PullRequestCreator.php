<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

use EventManager;
use GitRepository;
use Tuleap\PullRequest\Comment\Comment;
use Tuleap\PullRequest\Exception\PullRequestRepositoryMigratedOnGerritException;
use Tuleap\PullRequest\Exception\PullRequestAlreadyExistsException;
use Tuleap\PullRequest\Exception\PullRequestAnonymousUserException;
use Tuleap\PullRequest\Exception\PullRequestTargetException;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceCreator;

class PullRequestCreator
{
    public function __construct(
        private readonly PullRequestCreatorChecker $creator_checker,
        private readonly Factory $pull_request_factory,
        private readonly PullRequestMerger $pull_request_merger,
        private readonly EventManager $event_manager,
        private readonly GitPullRequestReferenceCreator $git_pull_request_reference_creator,
    ) {
    }

    /**
     * @throws Exception\PullRequestNotCreatedException
     * @throws Exception\UnknownBranchNameException
     * @throws Exception\PullRequestCannotBeCreatedException
     * @throws PullRequestAlreadyExistsException
     * @throws PullRequestAnonymousUserException
     * @throws PullRequestRepositoryMigratedOnGerritException
     * @throws PullRequestTargetException
     * @throws \Git_Command_Exception
     */
    public function generatePullRequest(
        GitRepository $repository_src,
        string $branch_src,
        GitRepository $repository_dest,
        string $branch_dest,
        \PFUser $creator,
        string|null $description_format,
    ): PullRequest {
        $executor_repository_source      = new GitExec($repository_src->getFullPath(), $repository_src->getFullPath());
        $executor_repository_destination = GitExec::buildFromRepository($repository_dest);
        $sha1_src                        = $executor_repository_source->getBranchSha1("refs/heads/$branch_src");
        $sha1_dest                       = $executor_repository_destination->getBranchSha1("refs/heads/$branch_dest");
        $repo_dest_id                    = $repository_dest->getId();
        $repo_src_id                     = $repository_src->getId();

        $this->creator_checker->checkIfPullRequestCanBeCreated(
            $creator,
            $repository_src,
            $branch_src,
            $sha1_src,
            $repository_dest,
            $branch_dest,
        );

        $commit_message = $executor_repository_source->getCommitMessage($sha1_src);
        $split_commit   = explode("\n", $commit_message, 2);
        $first_line     = $split_commit[0];
        $other_lines    = ltrim($split_commit[1] ?? '');

        $pull_request = new PullRequest(
            0,
            $first_line,
            $other_lines,
            $repo_src_id,
            $creator->getId(),
            time(),
            $branch_src,
            $sha1_src,
            $repo_dest_id,
            $branch_dest,
            $sha1_dest,
            $description_format ?: Comment::FORMAT_MARKDOWN
        );
        $pull_request = $this->pull_request_factory->create($creator, $pull_request, (int) $repository_src->getProjectId());

        $this->git_pull_request_reference_creator->createPullRequestReference(
            $pull_request,
            $executor_repository_source,
            $executor_repository_destination,
            $repository_dest
        );

        $merge_status = $this->pull_request_merger->detectMergeabilityStatus(
            $executor_repository_destination,
            $pull_request->getSha1Src(),
            $pull_request->getSha1Dest()
        );
        $pull_request = $pull_request->updateMergeStatus($merge_status);
        $this->pull_request_factory->updateMergeStatus($pull_request, $merge_status);

        $event = new GetCreatePullRequest($pull_request, $creator, $repository_dest->getProject());
        $this->event_manager->processEvent($event);

        return $pull_request;
    }
}
