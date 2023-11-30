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

namespace Tuleap\PullRequest\REST\v1;

use GitRepository;
use Luracast\Restler\RestException;
use Tuleap\Git\RetrieveGitRepository;
use Tuleap\Git\Tests\Stub\RetrieveGitRepositoryStub;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceRetriever;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequest\REST\v1\AccessiblePullRequestRESTRetriever;
use Tuleap\PullRequest\PullRequest\REST\v1\PullRequestWithGitReferenceRetriever;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\PullRequest\PullRequestWithGitReference;
use Tuleap\PullRequest\Tests\Builders\GitPullRequestReferenceTestBuilder;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\PullRequest\Tests\Stub\CheckUserCanAccessPullRequestStub;
use Tuleap\PullRequest\Tests\Stub\GetReferenceByPullRequestIdStub;
use Tuleap\PullRequest\Tests\Stub\SearchPullRequestStub;
use Tuleap\PullRequest\Tests\Stub\UpdateGitPullRequestReferenceStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class PullRequestWithGitReferenceRetrieverTest extends TestCase
{
    private const PULL_REQUEST_ID = 15;
    private const REFERENCE_ID    = 150;
    private GetReferenceByPullRequestIdStub $git_pull_request_reference_dao;
    private RetrieveGitRepository $git_repository_factory;
    private SearchPullRequestStub $pull_request_dao;
    private CheckUserCanAccessPullRequestStub $permission_checker;
    private UpdateGitPullRequestReferenceStub $git_pull_request_reference_updater;
    private PullRequest $pull_request;

    protected function setUp(): void
    {
        $this->pull_request = PullRequestTestBuilder::aPullRequestInReview()
            ->withId(self::PULL_REQUEST_ID)
            ->withTitle('CTR "Yellowbird"')
            ->build();
        $reference          = GitPullRequestReferenceTestBuilder::aReference(self::REFERENCE_ID)->build();

        $this->pull_request_dao               = SearchPullRequestStub::withAtLeastOnePullRequest($this->pull_request);
        $this->git_pull_request_reference_dao = GetReferenceByPullRequestIdStub::withPullRequestWithReference(
            new PullRequestWithGitReference($this->pull_request, $reference)
        );

        $this->git_repository_factory             = RetrieveGitRepositoryStub::withGitRepository(new GitRepository());
        $this->permission_checker                 = CheckUserCanAccessPullRequestStub::withAllowed();
        $this->git_pull_request_reference_updater = UpdateGitPullRequestReferenceStub::build();
    }

    /**
     * @throws RestException
     */
    private function getAccessiblePullRequestWithGitReferenceForCurrentUser(): PullRequestWithGitReference
    {
        $user = UserTestBuilder::buildWithDefaults();

        $pull_request_with_git_reference_retriever = new PullRequestWithGitReferenceRetriever(
            new GitPullRequestReferenceRetriever($this->git_pull_request_reference_dao),
            $this->git_pull_request_reference_updater,
            $this->git_repository_factory,
            new AccessiblePullRequestRESTRetriever(
                new PullRequestRetriever($this->pull_request_dao),
                $this->permission_checker
            )
        );

        return $pull_request_with_git_reference_retriever->getAccessiblePullRequestWithGitReferenceForCurrentUser(
            self::PULL_REQUEST_ID,
            $user
        );
    }

    public function testItThrows404IfTheGitReferenceOfThePullRequestIsNotFound(): void
    {
        $this->git_pull_request_reference_dao = GetReferenceByPullRequestIdStub::withoutRow();

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);
        $this->expectExceptionMessage('No Git reference is reserved for this pull request');

        $this->getAccessiblePullRequestWithGitReferenceForCurrentUser();
        self::assertSame(0, $this->git_pull_request_reference_updater->getUpdatePullRequestReferenceCallCount());
    }

    public function testItThrows410IfTheGitReferenceIsBroken(): void
    {
        $reference = GitPullRequestReferenceTestBuilder::aReference(self::REFERENCE_ID)
            ->thatIsBroken()
            ->build();

        $this->git_pull_request_reference_dao = GetReferenceByPullRequestIdStub::withPullRequestWithReference(
            new PullRequestWithGitReference($this->pull_request, $reference)
        );

        $this->expectException(RestException::class);
        $this->expectExceptionCode(410);
        $this->expectExceptionMessage('The pull request is not accessible anymore');

        $this->getAccessiblePullRequestWithGitReferenceForCurrentUser();
        self::assertSame(0, $this->git_pull_request_reference_updater->getUpdatePullRequestReferenceCallCount());
    }

    public function testItReturnsThePullRequestWithTheGitReference(): void
    {
        $result = $this->getAccessiblePullRequestWithGitReferenceForCurrentUser();

        self::assertSame(self::REFERENCE_ID, $result->getGitReference()->getGitReferenceId());
        self::assertSame(self::PULL_REQUEST_ID, $result->getPullRequest()->getId());
        self::assertSame('CTR "Yellowbird"', $result->getPullRequest()->getTitle());
        self::assertSame(0, $this->git_pull_request_reference_updater->getUpdatePullRequestReferenceCallCount());
    }

    public function testItThrowsIfTheWantedGitRepositoryDoesNotExist(): void
    {
        $reference = GitPullRequestReferenceTestBuilder::aReference(self::REFERENCE_ID)
            ->thatIsNotYetCreated()
            ->build();

        $this->git_pull_request_reference_dao = GetReferenceByPullRequestIdStub::withPullRequestWithReference(
            new PullRequestWithGitReference($this->pull_request, $reference)
        );

        $this->git_repository_factory = RetrieveGitRepositoryStub::withoutGitRepository();

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);
        $this->expectExceptionMessage("Git repository not found");

        $this->getAccessiblePullRequestWithGitReferenceForCurrentUser();
        self::assertSame(0, $this->git_pull_request_reference_updater->getUpdatePullRequestReferenceCallCount());
    }

    public function testItReturnsThePullRequestWithTheGitReferenceAndItTheUpdateTheGitReference(): void
    {
        // Needed because of the usage of GitExec::buildFromRepository which call `GitRepository::getFullPath
        $git_repository = $this->createMock(GitRepository::class);
        $git_repository->method("getFullPath")->willReturn("/repo.git");

        $this->git_repository_factory = RetrieveGitRepositoryStub::withGitRepository($git_repository);

        $reference = GitPullRequestReferenceTestBuilder::aReference(self::REFERENCE_ID)
            ->thatIsNotYetCreated()
            ->build();

        $this->git_pull_request_reference_dao = GetReferenceByPullRequestIdStub::withPullRequestWithReference(
            new PullRequestWithGitReference($this->pull_request, $reference)
        );

        $result = $this->getAccessiblePullRequestWithGitReferenceForCurrentUser();

        self::assertSame(150, $result->getGitReference()->getGitReferenceId());
        self::assertSame(15, $result->getPullRequest()->getId());
        self::assertSame('CTR "Yellowbird"', $result->getPullRequest()->getTitle());
        self::assertSame(1, $this->git_pull_request_reference_updater->getUpdatePullRequestReferenceCallCount());
    }
}
