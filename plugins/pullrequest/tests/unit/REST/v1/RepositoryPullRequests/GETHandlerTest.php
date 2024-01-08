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

namespace Tuleap\PullRequest\REST\v1\RepositoryPullRequests;

use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Git\Tests\Stub\RetrieveGitRepositoryStub;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\PullRequest\Criterion\MalformedQueryFault;
use Tuleap\PullRequest\GitReference\GetReferenceByPullRequestId;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceNotFoundFault;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceRetriever;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequest\REST\v1\PullRequestAuthorNotFoundFault;
use Tuleap\PullRequest\PullRequest\REST\v1\RepositoryPullRequests\GETHandler;
use Tuleap\PullRequest\PullRequestWithGitReference;
use Tuleap\PullRequest\REST\v1\RepositoryPullRequestRepresentation;
use Tuleap\PullRequest\Tests\Builders\GitPullRequestReferenceTestBuilder;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\PullRequest\Tests\Stub\GenerateGitoliteAccessURLStub;
use Tuleap\PullRequest\Tests\Stub\GetReferenceByPullRequestIdStub;
use Tuleap\PullRequest\Tests\Stub\RetrieveReviewersStub;
use Tuleap\PullRequest\Tests\Stub\SearchPaginatedPullRequestsStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;

final class GETHandlerTest extends TestCase
{
    private const REFERENCE_ID = 10523;
    private const OFFSET       = 0;
    private const LIMIT        = 50;

    private \GitRepository $repository;
    private PullRequest $pull_request;
    private RetrieveUserByIdStub $retrieve_user_by_id;
    private GetReferenceByPullRequestId $get_reference_by_pull_request_id_stub;

    protected function setUp(): void
    {
        $this->repository = GitRepositoryTestBuilder::aProjectRepository()->build();

        $pull_request_author_id    = 102;
        $this->pull_request        = PullRequestTestBuilder::aPullRequestInReview()->createdBy($pull_request_author_id)->build();
        $this->retrieve_user_by_id = RetrieveUserByIdStub::withUser(UserTestBuilder::anActiveUser()->withId($pull_request_author_id)->build());

        $reference = new PullRequestWithGitReference(
            $this->pull_request,
            GitPullRequestReferenceTestBuilder::aReference(self::REFERENCE_ID)->build()
        );

        $this->get_reference_by_pull_request_id_stub = GetReferenceByPullRequestIdStub::withPullRequestWithReference($reference);
    }

    public function testItReturnsAnErrorWhenTheQueryIsMalformed(): void
    {
        $query  = json_encode("I have no idea what I'm doing", JSON_THROW_ON_ERROR);
        $result = $this->handle($query);

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(MalformedQueryFault::class, $result->error);
    }

    public function testItReturnsAnErrorWhenTheAuthorOfAPullRequestCannotBeFound(): void
    {
        $query                     = json_encode(['status' => 'open'], JSON_THROW_ON_ERROR);
        $this->retrieve_user_by_id = RetrieveUserByIdStub::withNoUser();

        $result = $this->handle($query);

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(PullRequestAuthorNotFoundFault::class, $result->error);
    }

    public function testItReturnsAnErrorWhenTheGitReferenceOfAPullRequestCannotBeFound(): void
    {
        $query                                       = json_encode(['status' => 'open'], JSON_THROW_ON_ERROR);
        $this->get_reference_by_pull_request_id_stub = GetReferenceByPullRequestIdStub::withoutRow();

        $result = $this->handle($query);

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(GitPullRequestReferenceNotFoundFault::class, $result->error);
    }

    public function testItReturnsARepositoryPullRequestRepresentation(): void
    {
        $query  = json_encode(['status' => 'open'], JSON_THROW_ON_ERROR);
        $result = $this->handle($query);

        self::assertTrue(Result::isOk($result));

        self::assertEquals(1, $result->unwrapOr(null)->total_size);
        self::assertNotEmpty($result->unwrapOr([])->collection);
    }

    /**
     * @return Ok<RepositoryPullRequestRepresentation> | Err<Fault>
     */
    public function handle(string $query): Ok | Err
    {
        $handler = new GETHandler(
            new QueryToSearchCriteriaConverter(),
            SearchPaginatedPullRequestsStub::withAtLeastOnePullRequest($this->pull_request),
            $this->retrieve_user_by_id,
            RetrieveGitRepositoryStub::withGitRepository($this->repository),
            RetrieveGitRepositoryStub::withGitRepository($this->repository),
            new GitPullRequestReferenceRetriever($this->get_reference_by_pull_request_id_stub),
            RetrieveReviewersStub::withReviewers(
                UserTestBuilder::anActiveUser()->build(),
                UserTestBuilder::anActiveUser()->build(),
            ),
            new GenerateGitoliteAccessURLStub(),
            new TestLogger(),
        );

        return $handler->handle(
            $this->repository,
            $query,
            self::LIMIT,
            self::OFFSET
        );
    }
}
