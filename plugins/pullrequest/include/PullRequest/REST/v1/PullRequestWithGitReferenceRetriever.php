<?php
/**
 * Copyright (c) Enalean, 2023-present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\PullRequest\PullRequest\REST\v1;

use GitRepository;
use Luracast\Restler\RestException;
use PFUser;
use Tuleap\Git\RetrieveGitRepository;
use Tuleap\PullRequest\GitExec;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceNotFoundException;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceRetriever;
use Tuleap\PullRequest\GitReference\UpdateGitPullRequestReference;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequestWithGitReference;

final class PullRequestWithGitReferenceRetriever
{
    public function __construct(
        private readonly GitPullRequestReferenceRetriever $git_pull_request_reference_retriever,
        private readonly UpdateGitPullRequestReference $git_pull_request_reference_updater,
        private readonly RetrieveGitRepository $git_repository_factory,
        private readonly AccessiblePullRequestRESTRetriever $accessible_pull_request_REST_retriever,
    ) {
    }

    /**
     * @throws RestException
     */
    public function getAccessiblePullRequestWithGitReferenceForCurrentUser(int $pull_request_id, PFUser $current_user): PullRequestWithGitReference
    {
        try {
            $pull_request = $this->accessible_pull_request_REST_retriever->getAccessiblePullRequest($pull_request_id, $current_user);

            $git_reference = $this->git_pull_request_reference_retriever->getGitReferenceFromPullRequest($pull_request);
        } catch (GitPullRequestReferenceNotFoundException $exception) {
            throw new RestException(404, $exception->getMessage());
        }

        if ($git_reference->isGitReferenceBroken()) {
            throw new RestException(
                410,
                dgettext('tuleap-pullrequest', 'The pull request is not accessible anymore')
            );
        }

        if ($git_reference->isGitReferenceNeedToBeCreatedInRepository()) {
            $this->updateGitReference($pull_request);
        }

        return new PullRequestWithGitReference($pull_request, $git_reference);
    }

    /**
     * @throws RestException
     */
    private function updateGitReference(PullRequest $pull_request): void
    {
        $repository_source      = $this->getRepository($pull_request->getRepositoryId());
        $repository_destination = $this->getRepository($pull_request->getRepoDestId());
        $this->git_pull_request_reference_updater->updatePullRequestReference(
            $pull_request,
            GitExec::buildFromRepository($repository_source),
            GitExec::buildFromRepository($repository_destination),
            $repository_destination
        );
    }

    /**
     * @throws RestException
     */
    private function getRepository(int $repository_id): GitRepository
    {
        $repository = $this->git_repository_factory->getRepositoryById($repository_id);

        if (! $repository) {
            throw new RestException(404, "Git repository not found");
        }

        return $repository;
    }
}
