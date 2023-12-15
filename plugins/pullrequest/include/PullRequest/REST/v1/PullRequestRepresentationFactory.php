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

namespace Tuleap\PullRequest\REST\v1;

use Codendi_HTMLPurifier;
use Tuleap\Git\CommitStatus\CommitStatusRetriever;
use Tuleap\Git\Gitolite\GitoliteAccessURLGenerator;
use Tuleap\Git\Permissions\AccessControlVerifier;
use Tuleap\Markdown\ContentInterpretor;
use Tuleap\PullRequest\GitExec;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequestWithGitReference;
use Tuleap\PullRequest\REST\v1\Reviewer\ReviewersRepresentation;
use Tuleap\PullRequest\Reviewer\ReviewerRetriever;

class PullRequestRepresentationFactory
{
    public const BUILD_STATUS_UNKNOWN = 'unknown';
    public const BUILD_STATUS_SUCCESS = 'success';
    public const BUILD_STATUS_FAIL    = 'fail';
    public const BUILD_STATUS_PENDING = 'pending';

    public function __construct(
        private readonly AccessControlVerifier $access_control_verifier,
        private readonly CommitStatusRetriever $commit_status_retriever,
        private readonly GitoliteAccessURLGenerator $gitolite_access_URL_generator,
        private readonly PullRequestStatusInfoRepresentationBuilder $status_info_representation_builder,
        private readonly Codendi_HTMLPurifier $purifier,
        private readonly ContentInterpretor $common_mark_interpreter,
        private readonly ReviewerRetriever $reviewer_retriever,
    ) {
    }

    /**
     * @throws \Git_Command_Exception
     */
    public function getPullRequestRepresentation(
        PullRequestWithGitReference $pull_request_with_git_reference,
        \GitRepository $repository_src,
        \GitRepository $repository_dest,
        GitExec $executor_repository_destination,
        \PFUser $user,
    ) {
        $pull_request = $pull_request_with_git_reference->getPullRequest();

        $short_stat        = $executor_repository_destination->getShortStat(
            $pull_request->getSha1Dest(),
            $pull_request->getSha1Src()
        );
        $short_stat_repres = new PullRequestShortStatRepresentation($short_stat);

        $user_can_merge   = $this->access_control_verifier->canWrite($user, $repository_dest, $pull_request->getBranchDest());
        $user_can_abandon = $user_can_merge ||
            $this->access_control_verifier->canWrite($user, $repository_src, $pull_request->getBranchSrc());

        $user_can_reopen = (
            $pull_request->getStatus() === PullRequest::STATUS_ABANDONED &&
            $this->access_control_verifier->canWrite($user, $repository_dest, $pull_request->getBranchDest()) &&
            $this->access_control_verifier->canWrite($user, $repository_src, $pull_request->getBranchSrc())
        );

        $user_can_update_labels = $user_can_merge;

        [$last_build_status_name, $last_build_date] = $this->getLastBuildInformation($pull_request, $repository_dest);

        $reviewers = $this->reviewer_retriever->getReviewers($pull_request);

        $pull_request_representation = new PullRequestRepresentation($this->gitolite_access_URL_generator);
        $pull_request_representation->build(
            $this->purifier,
            $this->common_mark_interpreter,
            $pull_request,
            $repository_src,
            $repository_dest,
            $pull_request_with_git_reference->getGitReference(),
            $user_can_merge,
            $user_can_abandon,
            $user_can_reopen,
            $user_can_update_labels,
            $last_build_status_name,
            $last_build_date,
            $user,
            ReviewersRepresentation::fromUsers(...$reviewers)->users,
            $short_stat_repres,
            $this->status_info_representation_builder->buildPullRequestStatusInfoRepresentation($pull_request)
        );

        return $pull_request_representation;
    }

    /**
     * @return array{string, int|null}
     */
    private function getLastBuildInformation(PullRequest $pull_request, \GitRepository $repository_destination): array
    {
        $commit_status = $this->commit_status_retriever->getLastCommitStatus(
            $repository_destination,
            $pull_request->getSha1Src()
        );

        switch ($commit_status->getStatusName()) {
            case self::BUILD_STATUS_SUCCESS:
                return [self::BUILD_STATUS_SUCCESS, $commit_status->getDate()->getTimestamp()];
            case 'failure':
                return [self::BUILD_STATUS_FAIL, $commit_status->getDate()->getTimestamp()];
            case self::BUILD_STATUS_PENDING:
                return [self::BUILD_STATUS_PENDING, $commit_status->getDate()->getTimestamp()];
            default:
                return [self::BUILD_STATUS_UNKNOWN, null];
        }
    }
}
