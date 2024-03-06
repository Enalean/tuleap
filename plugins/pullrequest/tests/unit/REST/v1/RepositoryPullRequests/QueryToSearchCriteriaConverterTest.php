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
        self::assertTrue($result->unwrapOr(null)->status->isNothing());
        self::assertEmpty($result->unwrapOr(null)->authors);
    }

    public function testItReturnsAnErrorWhenTheStatusToFilterOnIsInvalid(): void
    {
        $result = $this->converter->convert(json_encode(['status' => 'unknown'], JSON_THROW_ON_ERROR));

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(MalformedQueryFault::class, $result->error);
        self::assertStringContainsString("status", (string) $result->error);
    }

    public function testItWillFilterOnOpenPullRequestsOnly(): void
    {
        $result = $this->converter->convert(json_encode(['status' => 'open'], JSON_THROW_ON_ERROR));

        self::assertTrue(Result::isOk($result));

        $status_criterion = $result->unwrapOr(null)->status;

        self::assertTrue($status_criterion->unwrapOr(null)->shouldOnlyRetrieveOpenPullRequests());
        self::assertFalse($status_criterion->unwrapOr(null)->shouldOnlyRetrieveClosedPullRequests());
    }

    public function testItWillFilterOnClosedPullRequestsOnly(): void
    {
        $result = $this->converter->convert(json_encode(['status' => 'closed'], JSON_THROW_ON_ERROR));

        self::assertTrue(Result::isOk($result));

        $criteria         = $result->unwrapOr(null);
        $status_criterion = $criteria->status->unwrapOr(null);

        self::assertFalse($status_criterion->shouldOnlyRetrieveOpenPullRequests());
        self::assertTrue($status_criterion->shouldOnlyRetrieveClosedPullRequests());
    }

    public function testItReturnsAnErrorWhenTheAuthorToFilterOnIsInvalid(): void
    {
        $result = $this->converter->convert(json_encode(['authors' => 'myself'], JSON_THROW_ON_ERROR));

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(MalformedQueryFault::class, $result->error);
        self::assertStringContainsString("authors", (string) $result->error);
    }

    public function testItReturnsAnErrorWhenTheAuthorIdIsNotAnInt(): void
    {
        $result = $this->converter->convert(json_encode(['authors' => [['id' => "one-hundred-and-two"]]], JSON_THROW_ON_ERROR));

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(MalformedQueryFault::class, $result->error);
        self::assertStringContainsString("authors", (string) $result->error);
    }

    public function testItReturnsAnErrorWhenTryingToFilterMultipleAuthors(): void
    {
        $result = $this->converter->convert(json_encode(['authors' => [['id' => 102], ['id' => 103]]], JSON_THROW_ON_ERROR));

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(MalformedQueryFault::class, $result->error);
        self::assertStringContainsString("authors", (string) $result->error);
    }

    public function testItWillOnlyFilterOnAuthor(): void
    {
        $result = $this->converter->convert(json_encode(['authors' => [['id' => 102]]], JSON_THROW_ON_ERROR));

        self::assertTrue(Result::isOk($result));

        $criteria = $result->unwrapOr(null);

        self::assertCount(1, $criteria->authors);
        self::assertEquals(102, $criteria->authors[0]->id);
    }

    public function testItWillOnlyFilterOnLabels(): void
    {
        $result = $this->converter->convert(json_encode(['labels' => [['id' => 1], ['id' => 2]]], JSON_THROW_ON_ERROR));

        self::assertTrue(Result::isOk($result));

        $criteria = $result->unwrapOr(null);

        self::assertEquals(1, $criteria->labels[0]->id);
        self::assertEquals(2, $criteria->labels[1]->id);
    }

    public function testItReturnsAnErrorWhenTheLabelsIdsAreNotIntegers(): void
    {
        $result = $this->converter->convert(json_encode(['labels' => [['id' => "1"]]], JSON_THROW_ON_ERROR));

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(MalformedQueryFault::class, $result->error);
        self::assertStringContainsString("labels", (string) $result->error);
    }

    public function testItWillFilterOnKeywords(): void
    {
        $result = $this->converter->convert(json_encode(['search' => [['keyword' => 'security'], ['keyword' => 'bump']]], JSON_THROW_ON_ERROR));

        self::assertTrue(Result::isOk($result));

        $criteria = $result->unwrapOr(null);

        self::assertEquals("security", $criteria->search[0]->keyword);
        self::assertEquals("bump", $criteria->search[1]->keyword);
    }

    public function testItReturnsAnErrorWhenTryingToFilterMultipleTargetBranches(): void
    {
        $result = $this->converter->convert(json_encode(['target_branches' => [['name' => 'walnut'], ['name' => 'palm tree']]], JSON_THROW_ON_ERROR));

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(MalformedQueryFault::class, $result->error);
        self::assertStringContainsString("target_branches", (string) $result->error);
    }

    public function testItWillFilterOnTargetBranches(): void
    {
        $result = $this->converter->convert(json_encode(['target_branches' => [['name' => 'walnut']]], JSON_THROW_ON_ERROR));

        self::assertTrue(Result::isOk($result));

        $criteria = $result->unwrapOr(null);

        self::assertEquals("walnut", $criteria->target_branches[0]->name);
    }

    public function testItReturnsAnErrorWhenTryingToFilterMultipleReviewers(): void
    {
        $result = $this->converter->convert(json_encode(['reviewers' => [['id' => 102], ['id' => 103]]], JSON_THROW_ON_ERROR));

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(MalformedQueryFault::class, $result->error);
        self::assertStringContainsString("reviewers", (string) $result->error);
    }

    public function testItWillFilterOnReviewers(): void
    {
        $result = $this->converter->convert(json_encode(['reviewers' => [['id' => 102]]], JSON_THROW_ON_ERROR));

        self::assertTrue(Result::isOk($result));

        $criteria = $result->unwrapOr(null);

        self::assertEquals(102, $criteria->reviewers[0]->id);
    }

    public function testItWillApplyAllFilters(): void
    {
        $result = $this->converter->convert(
            json_encode([
                'status' => 'open',
                'authors' => [['id' => 102]],
                'labels' => [['id' => 1], ['id' => 2]],
                'search' => [['keyword' => 'security'], ['keyword' => 'bump']],
                'target_branches' => [['name' => 'walnut']],
                'reviewers' => [["id" => 102]],
            ], JSON_THROW_ON_ERROR)
        );

        self::assertTrue(Result::isOk($result));

        $criteria         = $result->unwrapOr(null);
        $status_criterion = $criteria->status->unwrapOr(null);

        self::assertEquals(102, $criteria->authors[0]->id);
        self::assertEquals(1, $criteria->labels[0]->id);
        self::assertEquals(2, $criteria->labels[1]->id);
        self::assertEquals("security", $criteria->search[0]->keyword);
        self::assertEquals("bump", $criteria->search[1]->keyword);
        self::assertEquals("walnut", $criteria->target_branches[0]->name);
        self::assertEquals(102, $criteria->reviewers[0]->id);

        self::assertTrue($status_criterion->shouldOnlyRetrieveOpenPullRequests());
        self::assertFalse($status_criterion->shouldOnlyRetrieveClosedPullRequests());
    }
}
