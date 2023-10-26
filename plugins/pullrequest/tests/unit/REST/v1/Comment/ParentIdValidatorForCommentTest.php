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

use Tuleap\PullRequest\Comment\Comment;
use Tuleap\PullRequest\Comment\CommentRetriever;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\PullRequest\Tests\Stub\CommentSearcherStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class ParentIdValidatorForCommentTest extends TestCase
{
    private const PULL_REQUEST_ID = 10;
    private CommentSearcherStub $comment_dao;

    protected function setUp(): void
    {
        $this->comment_dao = CommentSearcherStub::withDefaultRow();
    }

    private function checkParentValidity(int $parent_id): void
    {
        $validator = new ParentIdValidatorForComment(new CommentRetriever($this->comment_dao));

        $validator->checkParentValidity($parent_id, self::PULL_REQUEST_ID);
    }

    public function testItDoesNothingIfParentIdIsZero(): void
    {
        self::expectNotToPerformAssertions();
        $this->checkParentValidity(0);
    }

    public function testItThrowsAnExceptionWhenCommentIsNotAddedOnARootComment(): void
    {
        $parent_id         = 1;
        $comment           = new Comment(
            1,
            self::PULL_REQUEST_ID,
            (int) UserTestBuilder::anActiveUser()->build()->getId(),
            time(),
            "My content",
            1234,
            "graffiti-yellow",
            TimelineComment::FORMAT_TEXT
        );
        $this->comment_dao = CommentSearcherStub::fromComment($comment);

        self::expectExceptionCode(400);
        self::expectExceptionMessage("first comment of thread");
        $this->checkParentValidity($parent_id);
    }

    public function testItThrowsAnExceptionWhenCommentIsNotAddedOnTheSamePullRequestThanTheProvidedOne(): void
    {
        $parent_id         = 1;
        $comment           = new Comment(
            1,
            1234,
            (int) UserTestBuilder::anActiveUser()->build()->getId(),
            time(),
            "My content",
            0,
            "graffiti-yellow",
            TimelineComment::FORMAT_TEXT
        );
        $this->comment_dao = CommentSearcherStub::fromComment($comment);

        self::expectExceptionCode(400);
        self::expectExceptionMessage("must be the same than provided comment");
        $this->checkParentValidity($parent_id);
    }

    public function testItDoesNotThrowIfParentIdIsValidForComment(): void
    {
        self::expectNotToPerformAssertions();

        $parent_id         = 1;
        $comment           = new Comment(
            1,
            self::PULL_REQUEST_ID,
            (int) UserTestBuilder::anActiveUser()->build()->getId(),
            time(),
            "My content",
            0,
            "graffiti-yellow",
            TimelineComment::FORMAT_TEXT
        );
        $this->comment_dao = CommentSearcherStub::fromComment($comment);

        $this->checkParentValidity($parent_id);
    }
}
