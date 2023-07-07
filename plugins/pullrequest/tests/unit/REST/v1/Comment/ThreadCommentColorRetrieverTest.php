<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1\Comment;

use Tuleap\PullRequest\Comment\Dao;
use Tuleap\PullRequest\Comment\ThreadCommentDao;
use Tuleap\Test\PHPUnit\TestCase;

final class ThreadCommentColorRetrieverTest extends TestCase
{
    private const PULLREQUEST_ID = 1;
    private const PARENT_ID      = 10;
    /**
     * @var ThreadCommentDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $thread_global;
    private ThreadCommentColorRetriever $color_assigner;
    /**
     * @var Dao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $comment_searcher;

    protected function setUp(): void
    {
        $this->thread_global    = $this->createMock(ThreadCommentDao::class);
        $this->comment_searcher = $this->createMock(Dao::class);
        $this->color_assigner   = new ThreadCommentColorRetriever($this->thread_global, $this->comment_searcher);
    }

    public function testItRetrieveColorForFirstThread(): void
    {
        $this->thread_global->method('searchAllThreadByPullRequestId')->willReturn([]);
        $this->comment_searcher->method('searchByCommentID')->willReturn(["color" => ""]);

        self::assertEquals("graffiti-yellow", $this->color_assigner->retrieveColor(self::PULLREQUEST_ID, self::PARENT_ID));
    }

    public function testItReturnsColorWhenPRHasMoreThanAThread(): void
    {
        $this->thread_global->method('searchAllThreadByPullRequestId')->willReturn([1]);
        $this->comment_searcher->method('searchByCommentID')->willReturn(["color" => ""]);
        self::assertEquals("daphne-blue", $this->color_assigner->retrieveColor(self::PULLREQUEST_ID, self::PARENT_ID));
    }

    public function testItReturnsSameColorWhenPRHasManyThreads(): void
    {
        $this->thread_global->method('searchAllThreadByPullRequestId')->willReturn([1, 2, 3, 4, 5, 6, 7, 8]);
        $this->comment_searcher->method('searchByCommentID')->willReturn(["color" => ""]);
        self::assertEquals("graffiti-yellow", $this->color_assigner->retrieveColor(self::PULLREQUEST_ID, self::PARENT_ID));
    }

    public function testItReturnsParentColor(): void
    {
        $this->comment_searcher->method('searchByCommentID')->willReturn(["color" => "flamingo-pink"]);
        $this->thread_global->method('searchAllThreadByPullRequestId')->willReturn([1]);
        self::assertEquals("flamingo-pink", $this->color_assigner->retrieveColor(self::PULLREQUEST_ID, self::PARENT_ID));
    }
}
