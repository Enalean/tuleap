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

use Tuleap\NeverThrow\Result;
use Tuleap\PullRequest\Criterion\MalformedQueryFault;
use Tuleap\PullRequest\Criterion\MalformedStatusQueryParameterFault;
use Tuleap\Test\PHPUnit\TestCase;

final class QueryToSearchCriteriaConverterTest extends TestCase
{
    private QueryToSearchCriteriaConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new QueryToSearchCriteriaConverter();
    }

    public function testItReturnsAnErrorWhenTheQueryIsNotAValidJSON(): void
    {
        $result = $this->converter->convert(json_encode(null, JSON_THROW_ON_ERROR));

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(MalformedQueryFault::class, $result->error);
    }

    public function testItReturnsOkWhenTheQueryIsAnEmptyJSON(): void
    {
        $result = $this->converter->convert(json_encode([], JSON_THROW_ON_ERROR));

        self::assertTrue(Result::isOk($result));
        self::assertTrue($result->unwrapOr(null)->getStatusCriterion()->isNothing());
    }

    public function testItReturnsAnErrorWhenTheStatusToFilterOnIsInvalid(): void
    {
        $result = $this->converter->convert(json_encode(['status' => 'unknown'], JSON_THROW_ON_ERROR));

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(MalformedStatusQueryParameterFault::class, $result->error);
    }

    public function testItWillFilterOnOpenPullRequestsOnly(): void
    {
        $result = $this->converter->convert(json_encode(['status' => 'open'], JSON_THROW_ON_ERROR));

        self::assertTrue(Result::isOk($result));

        $status_criterion = $result->unwrapOr(null)->getStatusCriterion();

        self::assertTrue($status_criterion->unwrapOr(null)->shouldOnlyRetrieveOpenPullRequests());
        self::assertFalse($status_criterion->unwrapOr(null)->shouldOnlyRetrieveClosedPullRequests());
    }

    public function testItWillFilterOnClosedPullRequestsOnly(): void
    {
        $result = $this->converter->convert(json_encode(['status' => 'closed'], JSON_THROW_ON_ERROR));

        self::assertTrue(Result::isOk($result));

        $status_criterion = $result->unwrapOr(null)->getStatusCriterion();

        self::assertFalse($status_criterion->unwrapOr(null)->shouldOnlyRetrieveOpenPullRequests());
        self::assertTrue($status_criterion->unwrapOr(null)->shouldOnlyRetrieveClosedPullRequests());
    }
}
