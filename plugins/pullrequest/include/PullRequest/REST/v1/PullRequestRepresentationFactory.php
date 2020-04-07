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

use Tuleap\Git\CommitStatus\CommitStatusRetriever;
use Tuleap\Git\Gitolite\GitoliteAccessURLGenerator;
use Tuleap\Git\Permissions\AccessControlVerifier;
use Tuleap\PullRequest\GitExec;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequestWithGitReference;

class PullRequestRepresentationFactory
{
    public const BUILD_STATUS_UNKNOWN = 'unknown';
    public const BUILD_STATUS_SUCESS  = 'success';
    public const BUILD_STATUS_FAIL    = 'fail';

    /**
     * @var AccessControlVerifier
     */
    private $access_control_verifier;
    /**
     * @var CommitStatusRetriever
     */
    private $commit_status_retriever;
    /**
     * @var GitoliteAccessURLGenerator
     */
    private $gitolite_access_URL_generator;

    public function __construct(
        AccessControlVerifier $access_control_verifier,
        CommitStatusRetriever $commit_status_retriever,
        GitoliteAccessURLGenerator $gitolite_access_URL_generator
    ) {
        $this->access_control_verifier       = $access_control_verifier;
        $this->commit_status_retriever       = $commit_status_retriever;
        $this->gitolite_access_URL_generator = $gitolite_access_URL_generator;
    }

    public function getPullRequestRepresentation(
        PullRequestWithGitReference $pull_request_with_git_reference,
        \GitRepository $repository_src,
        \GitRepository $repository_dest,
        GitExec $executor_repository_destination,
        \PFUser $user
    ) {
        $pull_request = $pull_request_with_git_reference->getPullRequest();

        $short_stat        = $executor_repository_destination->getShortStat(
            $pull_request->getSha1Dest(),
            $pull_request->getSha1Src()
        );
        $short_stat_repres = new PullRequestShortStatRepresentation();
        $short_stat_repres->build($short_stat);

        $user_can_merge   = $this->access_control_verifier->canWrite($user, $repository_dest, $pull_request->getBranchDest());
        $user_can_abandon = $user_can_merge ||
            $this->access_control_verifier->canWrite($user, $repository_src, $pull_request->getBranchSrc());

        $user_can_update_labels = $user_can_merge;

        [$last_build_status_name, $last_build_date] = $this->getLastBuildInformation($pull_request, $repository_dest);

        $pull_request_representation = new PullRequestRepresentation($this->gitolite_access_URL_generator);
        $pull_request_representation->build(
            $pull_request,
            $repository_src,
            $repository_dest,
            $pull_request_with_git_reference->getGitReference(),
            $user_can_merge,
            $user_can_abandon,
            $user_can_update_labels,
            $last_build_status_name,
            $last_build_date,
            $short_stat_repres
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
            case 'success':
                return [self::BUILD_STATUS_SUCESS, $commit_status->getDate()->getTimestamp()];
            case 'failure':
                return [self::BUILD_STATUS_FAIL, $commit_status->getDate()->getTimestamp()];
            default:
                return [self::BUILD_STATUS_UNKNOWN, null];
        }
    }
}
