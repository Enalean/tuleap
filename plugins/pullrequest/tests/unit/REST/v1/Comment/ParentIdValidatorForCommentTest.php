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

use Tuleap\PullRequest\Comment\CommentRetriever;
use Tuleap\PullRequest\Tests\Builders\CommentTestBuilder;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\PullRequest\Tests\Stub\CommentSearcherStub;
use Tuleap\Test\PHPUnit\TestCase;

final class ParentIdValidatorForCommentTest extends TestCase
{
    private const PULL_REQUEST_ID = 10;
    private int $parent_id;
    private CommentSearcherStub $comment_dao;

    protected function setUp(): void
    {
        $this->parent_id   = 1;
        $this->comment_dao = CommentSearcherStub::withNoComment();
    }

    private function checkParentValidity(): void
    {
        $validator = new ParentIdValidatorForComment(new CommentRetriever($this->comment_dao));

        $validator->checkParentValidity($this->parent_id, self::PULL_REQUEST_ID);
    }

    public function testItDoesNothingIfParentIdIsZero(): void
    {
        $this->parent_id = 0;
        $this->expectNotToPerformAssertions();
        $this->checkParentValidity();
    }

    public function testItThrowsAnExceptionIfParentIdIsNotFound(): void
    {
        $this->parent_id = -1;
        $this->expectExceptionCode(404);
        $this->checkParentValidity();
    }

    public function testItThrowsAnExceptionWhenCommentIsNotAddedOnARootComment(): void
    {
        $comment           = CommentTestBuilder::aMarkdownComment('predecease nonimmateriality')
            ->childOf(675)
            ->build();
        $this->comment_dao = CommentSearcherStub::withComment($comment);

        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('first comment of thread');
        $this->checkParentValidity();
    }

    public function testItThrowsAnExceptionWhenCommentIsNotAddedOnTheSamePullRequestThanTheProvidedOne(): void
    {
        $pull_request      = PullRequestTestBuilder::aPullRequestInReview()
            ->withId(1234)
            ->build();
        $comment           = CommentTestBuilder::aMarkdownComment('predecease nonimmateriality')
            ->onPullRequest($pull_request)
            ->build();
        $this->comment_dao = CommentSearcherStub::withComment($comment);

        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('must be the same than provided comment');
        $this->checkParentValidity();
    }

    public function testItDoesNotThrowIfParentIdIsValidForComment(): void
    {
        $pull_request      = PullRequestTestBuilder::aPullRequestInReview()
            ->withId(self::PULL_REQUEST_ID)
            ->build();
        $comment           = CommentTestBuilder::aMarkdownComment('predecease nonimmateriality')
            ->onPullRequest($pull_request)
            ->build();
        $this->comment_dao = CommentSearcherStub::withComment($comment);

        $this->expectNotToPerformAssertions();
        $this->checkParentValidity();
    }
}
