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

use Tuleap\PullRequest\InlineComment\InlineComment;
use Tuleap\PullRequest\InlineComment\InlineCommentRetriever;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class ParentIdValidatorForInlineCommentTest extends TestCase
{
    private ParentIdValidatorForInlineComment $validator;
    private const PULL_REQUEST_ID = 10;
    /**
     * @var InlineCommentRetriever&\PHPUnit\Framework\MockObject\MockObject
     */
    private $inline_comment_retriever;

    protected function setUp(): void
    {
        $this->inline_comment_retriever = $this->createMock(InlineCommentRetriever::class);
        $this->validator                = new ParentIdValidatorForInlineComment($this->inline_comment_retriever);
    }

    public function testItDoesNothingIfParentIdIsZero(): void
    {
        $this->expectNotToPerformAssertions();
        $this->validator->checkParentValidity(0, self::PULL_REQUEST_ID);
    }

    public function testItThrowAnExceptionIfParentIdDoesNotBelongToAComment(): void
    {
        $parent_id = 1;
        $this->inline_comment_retriever->method('getInlineCommentByID')->willReturn(null);

        $this->expectExceptionCode(404);
        $this->validator->checkParentValidity($parent_id, self::PULL_REQUEST_ID);
    }

    public function testItThrowAnExceptionIfParentIdDoesNotBelongToAInlineComment(): void
    {
        $parent_id = 1;
        $this->inline_comment_retriever->method('getInlineCommentByID')->willReturn(null);

        $this->expectExceptionCode(404);
        $this->validator->checkParentValidity($parent_id, self::PULL_REQUEST_ID);
    }

    public function testITThrowsAnExceptionWhenInlineCommentIsNotAddedOnTheSamePullRequestThanTheProvidedOne(): void
    {
        $parent_id = 1;
        $comment   = new InlineComment(
            1,
            1234,
            (int) UserTestBuilder::anActiveUser()->build()->getId(),
            time(),
            "/file/path",
            1,
            "My content",
            false,
            0,
            "right",
            "",
            TimelineComment::FORMAT_TEXT
        );
        $this->inline_comment_retriever->method('getInlineCommentByID')->willReturn($comment);

        $this->expectExceptionCode(400);
        $this->expectExceptionMessage("must be the same than provided comment");
        $this->validator->checkParentValidity($parent_id, self::PULL_REQUEST_ID);
    }

    public function testItDoesNotThrowIfParentIdIsValidForInlineComment(): void
    {
        $this->expectNotToPerformAssertions();
        $parent_id = 1;
        $comment   = new InlineComment(
            1,
            self::PULL_REQUEST_ID,
            (int) UserTestBuilder::anActiveUser()->build()->getId(),
            time(),
            "/file/path",
            1,
            "My content",
            false,
            0,
            "right",
            "",
            TimelineComment::FORMAT_TEXT
        );
        $this->inline_comment_retriever->method('getInlineCommentByID')->willReturn($comment);

        $this->validator->checkParentValidity($parent_id, self::PULL_REQUEST_ID);
    }
}
