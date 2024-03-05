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

namespace Tuleap\PullRequest\REST\v1\Reviewers;

use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\NeverThrow\Result;
use Tuleap\PullRequest\PullRequest\REST\v1\UserNotFoundFault;
use Tuleap\PullRequest\Tests\Stub\SearchRepositoryReviewersStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\User\REST\MinimalUserRepresentation;

final class GETHandlerTest extends TestCase
{
    private const LIMIT = 1;

    public function testItReturnsOkWithRepositoryPullRequestReviewersRepresentation(): void
    {
        $alice = UserTestBuilder::anActiveUser()->withId(102)->build();
        $bob   = UserTestBuilder::anActiveUser()->withId(103)->build();

        $handler = new GETHandler(
            RetrieveUserByIdStub::withUsers($alice, $bob),
            SearchRepositoryReviewersStub::withReviewers($alice, $bob)
        );

        $paginated_result_1 = $handler->handle(
            GitRepositoryTestBuilder::aProjectRepository()->build(),
            self::LIMIT,
            0
        );

        $paginated_result_2 = $handler->handle(
            GitRepositoryTestBuilder::aProjectRepository()->build(),
            self::LIMIT,
            1
        );

        self::assertTrue(Result::isOk($paginated_result_1));
        self::assertEquals(2, $paginated_result_1->value->total_size);
        self::assertCount(self::LIMIT, $paginated_result_1->value->collection);
        self::assertEquals(MinimalUserRepresentation::build($alice), $paginated_result_1->value->collection[0]);

        self::assertTrue(Result::isOk($paginated_result_2));
        self::assertEquals(2, $paginated_result_2->value->total_size);
        self::assertCount(self::LIMIT, $paginated_result_2->value->collection);
        self::assertEquals(MinimalUserRepresentation::build($bob), $paginated_result_2->value->collection[0]);
    }

    public function testItReturnsErrWhenAUserIsNotFound(): void
    {
        $alice    = UserTestBuilder::anActiveUser()->withId(102)->build();
        $hobo_joe = UserTestBuilder::anActiveUser()->withId(105)->build();

        $handler = new GETHandler(
            RetrieveUserByIdStub::withUsers($alice),
            SearchRepositoryReviewersStub::withReviewers($hobo_joe)
        );

        $result = $handler->handle(
            GitRepositoryTestBuilder::aProjectRepository()->build(),
            self::LIMIT,
            0
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(UserNotFoundFault::class, $result->error);
    }
}
