<?php
/**
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

namespace Tuleap\PullRequest\PullRequest\REST\v1\RepositoryPullRequests;

use GitRepository;
use Psr\Log\LoggerInterface;
use Tuleap\Git\Gitolite\GenerateGitoliteAccessURL;
use Tuleap\Git\RetrieveGitRepository;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\PullRequest\Criterion\PullRequestSortOrder;
use Tuleap\PullRequest\Criterion\SearchCriteria;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceNotFoundException;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceNotFoundFault;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceRetriever;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequest\REST\v1\PullRequestAuthorNotFoundFault;
use Tuleap\PullRequest\REST\v1\PullRequestMinimalRepresentation;
use Tuleap\PullRequest\REST\v1\RepositoryPullRequestRepresentation;
use Tuleap\PullRequest\REST\v1\RepositoryPullRequests\QueryToSearchCriteriaConverter;
use Tuleap\PullRequest\REST\v1\Reviewer\ReviewersRepresentation;
use Tuleap\PullRequest\Reviewer\RetrieveReviewers;
use Tuleap\PullRequest\SearchPaginatedPullRequests;
use Tuleap\User\REST\MinimalUserRepresentation;
use Tuleap\User\RetrieveUserById;

final class GETHandler
{
    public function __construct(
        private readonly QueryToSearchCriteriaConverter $query_to_search_criteria_converter,
        private readonly SearchPaginatedPullRequests $pull_request_dao,
        private readonly RetrieveUserById $retrieve_user_by_id,
        private readonly RetrieveGitRepository $retrieve_source_git_repository,
        private readonly RetrieveGitRepository $retrieve_destination_git_repository,
        private readonly GitPullRequestReferenceRetriever $git_pull_request_reference_retriever,
        private readonly RetrieveReviewers $retrieve_reviewers,
        private readonly GenerateGitoliteAccessURL $generate_gitolite_access_URL,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param "asc"|"desc" $order
     * @return Ok<RepositoryPullRequestRepresentation> | Err<Fault>
     */
    public function handle(GitRepository $repository, string $query, string $order, int $limit, int $offset): Ok|Err
    {
        return $this->query_to_search_criteria_converter->convert($query)->andThen(
            function (SearchCriteria $criteria) use ($order, $repository, $limit, $offset) {
                return $this->retrievePullRequests($repository, $criteria, PullRequestSortOrder::from($order), $limit, $offset);
            }
        );
    }

    /**
     * @return Ok<RepositoryPullRequestRepresentation> | Err<Fault>
     */
    private function retrievePullRequests(GitRepository $repository, SearchCriteria $criteria, PullRequestSortOrder $order, int $limit, int $offset): Ok|Err
    {
        $result = $this->pull_request_dao->getPaginatedPullRequests($repository->getId(), $criteria, $order, $limit, $offset);

        $collection = [];
        foreach ($result->pull_requests as $row) {
            $pull_request         = PullRequest::fromRow($row);
            $pull_request_creator = $this->retrieve_user_by_id->getUserById($pull_request->getUserId());
            if (! $pull_request_creator) {
                return Result::err(PullRequestAuthorNotFoundFault::fromPullRequest($pull_request));
            }

            $repository_src  = $this->retrieve_source_git_repository->getRepositoryById($pull_request->getRepositoryId());
            $repository_dest = $this->retrieve_destination_git_repository->getRepositoryById($pull_request->getRepoDestId());

            try {
                $git_reference = $this->git_pull_request_reference_retriever->getGitReferenceFromPullRequest($pull_request);
            } catch (GitPullRequestReferenceNotFoundException $e) {
                return Result::err(GitPullRequestReferenceNotFoundFault::build());
            }

            $reviewers                = $this->retrieve_reviewers->getReviewers($pull_request);
            $reviewers_representation = ReviewersRepresentation::fromUsers(...$reviewers);

            if ($repository_src && $repository_dest) {
                $pull_request_representation = new PullRequestMinimalRepresentation($this->generate_gitolite_access_URL);
                $pull_request_representation->buildMinimal(
                    $pull_request,
                    $repository_src,
                    $repository_dest,
                    $git_reference,
                    MinimalUserRepresentation::build($pull_request_creator),
                    $reviewers_representation->users
                );
                $collection[] = $pull_request_representation;
            } else {
                $this->logger->debug("Repository source or destination not found for pull-request " . $pull_request->getId());
            }
        }

        return Result::ok(new RepositoryPullRequestRepresentation($collection, $result->total_size));
    }
}
