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

use Tuleap\PullRequest\Tests\Stub\CountThreadsStub;
use Tuleap\PullRequest\Tests\Stub\ParentCommentSearcherStub;
use Tuleap\Test\PHPUnit\TestCase;

final class ThreadCommentColorRetrieverTest extends TestCase
{
    private const PULLREQUEST_ID = 1;
    private const PARENT_ID      = 10;
    private CountThreadsStub $thread_counter;
    private ParentCommentSearcherStub $comment_searcher;

    protected function setUp(): void
    {
        $this->thread_counter   = CountThreadsStub::withNumberOfThreads(0);
        $this->comment_searcher = ParentCommentSearcherStub::withParent(self::PARENT_ID, 0, '');
    }

    private function retrieve(): string
    {
        $colorer = new ThreadCommentColorRetriever($this->thread_counter, $this->comment_searcher);
        return $colorer->retrieveColor(self::PULLREQUEST_ID, self::PARENT_ID);
    }

    public function testItRetrieveColorForFirstThread(): void
    {
        self::assertSame(ThreadColors::TLP_COLORS[0], $this->retrieve());
    }

    public function testItReturnsColorWhenPRHasMoreThanOneThread(): void
    {
        $this->thread_counter = CountThreadsStub::withNumberOfThreads(1);
        self::assertSame(ThreadColors::TLP_COLORS[1], $this->retrieve());
    }

    public function testItReturnsSameColorWhenPRHasManyThreads(): void
    {
        $this->thread_counter = CountThreadsStub::withNumberOfThreads(8);
        self::assertSame(ThreadColors::TLP_COLORS[0], $this->retrieve());
    }

    public function testItReturnsParentColor(): void
    {
        $color                  = ThreadColors::TLP_COLORS[7];
        $this->comment_searcher = ParentCommentSearcherStub::withParent(self::PARENT_ID, 0, $color);
        $this->thread_counter   = CountThreadsStub::withNumberOfThreads(1);
        self::assertSame($color, $this->retrieve());
    }
}
