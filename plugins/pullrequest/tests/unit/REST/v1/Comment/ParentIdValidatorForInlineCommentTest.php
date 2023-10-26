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

use Tuleap\PullRequest\InlineComment\InlineCommentRetriever;
use Tuleap\PullRequest\Tests\Builders\InlineCommentTestBuilder;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\PullRequest\Tests\Stub\InlineCommentSearcherStub;
use Tuleap\Test\PHPUnit\TestCase;

final class ParentIdValidatorForInlineCommentTest extends TestCase
{
    private const PULL_REQUEST_ID = 10;
    private int $parent_id;
    private InlineCommentSearcherStub $comment_dao;

    protected function setUp(): void
    {
        $this->parent_id   = 1;
        $this->comment_dao = InlineCommentSearcherStub::withNoComment();
    }

    private function checkValidity(): void
    {
        $validator = new ParentIdValidatorForInlineComment(new InlineCommentRetriever($this->comment_dao));
        $validator->checkParentValidity($this->parent_id, self::PULL_REQUEST_ID);
    }

    public function testItDoesNothingIfParentIdIsZero(): void
    {
        $this->parent_id = 0;
        $this->expectNotToPerformAssertions();
        $this->checkValidity();
    }

    public function testItThrowsAnExceptionIfParentIdDoesNotBelongToAComment(): void
    {
        $this->parent_id = -1;
        $this->expectExceptionCode(404);
        $this->checkValidity();
    }

    public function testItThrowsWhenCommentIsNotAddedOnARootComment(): void
    {
        $comment           = InlineCommentTestBuilder::aMarkdownComment('siliquose fabiform')
            ->childOf(550)
            ->build();
        $this->comment_dao = InlineCommentSearcherStub::withComment($comment);

        $this->expectExceptionCode(400);
        $this->checkValidity();
    }

    public function testItThrowsAnExceptionWhenInlineCommentIsNotAddedOnTheSamePullRequestThanTheProvidedOne(): void
    {
        $pull_request      = PullRequestTestBuilder::aPullRequestInReview()
            ->withId(1234)
            ->build();
        $comment           = InlineCommentTestBuilder::aMarkdownComment('siliquose fabiform')
            ->onPullRequest($pull_request)
            ->build();
        $this->comment_dao = InlineCommentSearcherStub::withComment($comment);

        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('must be the same than provided comment');
        $this->checkValidity();
    }

    public function testItDoesNotThrowIfParentIdIsValidForInlineComment(): void
    {
        $pull_request      = PullRequestTestBuilder::aPullRequestInReview()
            ->withId(self::PULL_REQUEST_ID)
            ->build();
        $comment           = InlineCommentTestBuilder::aMarkdownComment('siliquose fabiform')
            ->onPullRequest($pull_request)
            ->build();
        $this->comment_dao = InlineCommentSearcherStub::withComment($comment);

        $this->expectNotToPerformAssertions();
        $this->checkValidity();
    }
}
