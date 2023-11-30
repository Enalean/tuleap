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

namespace Tuleap\PullRequest\Tests\REST\v1;

use GitRepoNotFoundException;
use Luracast\Restler\RestException;
use Project_AccessPrivateException;
use Project_AccessProjectNotFoundException;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequest\REST\v1\AccessiblePullRequestRESTRetriever;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\PullRequest\Tests\Stub\CheckUserCanAccessPullRequestStub;
use Tuleap\PullRequest\Tests\Stub\SearchPullRequestStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class AccessiblePullRequestRESTRetrieverTest extends TestCase
{
    private SearchPullRequestStub $pull_request_dao;
    private CheckUserCanAccessPullRequestStub $permission_checker;

    protected function setUp(): void
    {
        $this->pull_request_dao   = SearchPullRequestStub::withAtLeastOnePullRequest(PullRequestTestBuilder::aPullRequestInReview()->build());
        $this->permission_checker = CheckUserCanAccessPullRequestStub::withAllowed();
    }

    private function getAccessiblePullRequest(): PullRequest
    {
        $accessible_pull_request_retriever =
            new AccessiblePullRequestRESTRetriever(
                new PullRequestRetriever($this->pull_request_dao),
                $this->permission_checker
            );

        return $accessible_pull_request_retriever->getAccessiblePullRequest(15, UserTestBuilder::buildWithDefaults());
    }

    public static function dataProvider404ExceptionCases(): iterable
    {
        yield 'The repository of the pull request does not exist' => [new GitRepoNotFoundException()];
        yield 'Project access not found' => [new Project_AccessProjectNotFoundException()];
    }

    /**
     * @dataProvider dataProvider404ExceptionCases
     */
    public function testItMapsExceptionToRestExceptions(\Throwable $exception): void
    {
        $this->permission_checker = CheckUserCanAccessPullRequestStub::withException($exception);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);
        $this->getAccessiblePullRequest();
    }

    public function testItThrows403IfTheUserCannotAccessToTheProject(): void
    {
        $this->permission_checker = CheckUserCanAccessPullRequestStub::withException(new Project_AccessPrivateException());

        $this->expectException(RestException::class);
        $this->expectExceptionCode(403);

        $this->getAccessiblePullRequest();
    }

    public function testItThrows403IfTheUserCannotReadTheGitRepository(): void
    {
        $this->permission_checker = CheckUserCanAccessPullRequestStub::withException(new UserCannotReadGitRepositoryException());

        $this->expectException(RestException::class);
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage('User is not able to READ the git repository');

        $this->getAccessiblePullRequest();
    }

    public function testItReturnsThePullRequest(): void
    {
        $id            = 1;
        $title         = 'Elan Sprint';
        $repository_id = 15;

        $this->pull_request_dao = SearchPullRequestStub::withAtLeastOnePullRequest(
            PullRequestTestBuilder::aPullRequestInReview()->withId($id)->withTitle($title)->withRepositoryId($repository_id)->build()
        );
        $result                 = $this->getAccessiblePullRequest();

        self::assertSame($id, $result->getId());
        self::assertSame($title, $result->getTitle());
        self::assertSame($repository_id, $result->getRepositoryId());
    }
}
