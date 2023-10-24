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

namespace Tuleap\PullRequest;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\PullRequest\Tests\Stub\SearchPullRequestStub;
use Tuleap\Test\PHPUnit\TestCase;

final class PullRequestRetrieverTest extends TestCase
{
    private SearchPullRequestStub $pull_request_dao;

    protected function setUp(): void
    {
        $this->pull_request_dao = SearchPullRequestStub::withDefaultRow();
    }

    /**
     * @return Ok<PullRequest>|Err<Fault>
     */
    private function getPullRequestById(): Ok|Err
    {
        $pull_request_retriever = new PullRequestRetriever(
            $this->pull_request_dao
        );
        return $pull_request_retriever->getPullRequestById(1);
    }

    public function testItReturnsAnErrorIfThereIsNoPullRequestInDB(): void
    {
        $this->pull_request_dao = SearchPullRequestStub::withNoRow();

        $result = $this->getPullRequestById();

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(PullRequestNotFoundFault::class, $result->error);
    }

    public function testItReturnsThePullRequest(): void
    {
        $result = $this->getPullRequestById();

        self::assertTrue(Result::isOk($result));
        self::assertInstanceOf(PullRequest::class, $result->value);
        $pull_request = $result->value;
        self::assertSame(1, $pull_request->getId());
        self::assertSame('title', $pull_request->getTitle());
    }
}
