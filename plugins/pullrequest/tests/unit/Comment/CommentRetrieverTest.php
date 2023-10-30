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

namespace Tuleap\PullRequest\Comment;

use Tuleap\Option\Option;
use Tuleap\PullRequest\Tests\Builders\CommentTestBuilder;
use Tuleap\PullRequest\Tests\Stub\CommentSearcherStub;
use Tuleap\Test\PHPUnit\TestCase;

final class CommentRetrieverTest extends TestCase
{
    private const COMMENT_ID = 15;
    private CommentSearcherStub $comment_dao;

    protected function setUp(): void
    {
        $comment           = CommentTestBuilder::aMarkdownComment('squireless spitz')
            ->withId(self::COMMENT_ID)
            ->build();
        $this->comment_dao = CommentSearcherStub::withComment($comment);
    }

    /**
     * @return Option<Comment>
     */
    private function getCommentByID(): Option
    {
        $comment_retriever = new CommentRetriever($this->comment_dao);
        return $comment_retriever->getCommentByID(self::COMMENT_ID);
    }

    public function testItReturnsNothingIfTheCommentIsNotFound(): void
    {
        $this->comment_dao = CommentSearcherStub::withNoComment();
        $result            = $this->getCommentByID();

        self::assertTrue($result->isNothing());
    }

    public function testItReturnsTheComment(): void
    {
        $result = $this->getCommentByID();
        self::assertTrue($result->isValue());
    }
}
