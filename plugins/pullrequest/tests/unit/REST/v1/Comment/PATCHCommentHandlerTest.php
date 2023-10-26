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

namespace Tuleap\PullRequest\Tests\REST\v1\Comment;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\PullRequest\Authorization\CannotAccessToPullRequestFault;
use Tuleap\PullRequest\Comment\CommentFormatNotAllowedFault;
use Tuleap\PullRequest\Comment\CommentIsNotFromCurrentUserFault;
use Tuleap\PullRequest\Comment\CommentNotFoundFault;
use Tuleap\PullRequest\Comment\CommentRetriever;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\PullRequest\REST\v1\Comment\PATCHCommentHandler;
use Tuleap\PullRequest\REST\v1\CommentPATCHRepresentation;
use Tuleap\PullRequest\Tests\Builders\CommentTestBuilder;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\PullRequest\Tests\Stub\CheckUserCanAccessPullRequestStub;
use Tuleap\PullRequest\Tests\Stub\CommentSearcherStub;
use Tuleap\PullRequest\Tests\Stub\CommentUpdaterStub;
use Tuleap\PullRequest\Tests\Stub\SearchPullRequestStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class PATCHCommentHandlerTest extends TestCase
{
    public const CURRENT_USER_ID = 105;

    private CommentPATCHRepresentation $comment_data;
    private CommentSearcherStub $comment_dao_searcher;
    private CommentUpdaterStub $comment_updater_dao;
    private SearchPullRequestStub $pull_request_dao;
    private CheckUserCanAccessPullRequestStub $pull_request_permission_checker;
    private \PFUser $comment_author;

    protected function setUp(): void
    {
        $this->comment_data = new CommentPATCHRepresentation("B35");

        $this->comment_author = UserTestBuilder::buildWithId(self::CURRENT_USER_ID);

        $pull_request = PullRequestTestBuilder::aPullRequestInReview()->build();
        $comment      = CommentTestBuilder::aMarkdownComment('smarting meliponine')
            ->byAuthor($this->comment_author)
            ->onPullRequest($pull_request)
            ->build();

        $this->comment_dao_searcher            = CommentSearcherStub::withComment($comment);
        $this->comment_updater_dao             = CommentUpdaterStub::fromDefault();
        $this->pull_request_dao                = SearchPullRequestStub::withPullRequest($pull_request);
        $this->pull_request_permission_checker = CheckUserCanAccessPullRequestStub::withDefault();
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    private function handle(): Ok|Err
    {
        $put_handler_comment = new PATCHCommentHandler(
            new CommentRetriever($this->comment_dao_searcher),
            $this->comment_updater_dao,
            new PullRequestRetriever($this->pull_request_dao),
            $this->pull_request_permission_checker,
        );
        return $put_handler_comment->handle($this->comment_author, 1058, $this->comment_data);
    }

    public function testItReturnsAnErrorIfTheCommentIsNotFound(): void
    {
        $this->comment_dao_searcher = CommentSearcherStub::withNoComment();

        $result = $this->handle();

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(CommentNotFoundFault::class, $result->error);
        self::assertSame(0, $this->comment_updater_dao->getUpdateCommentMethodCount());
    }

    public function testItReturnsAnErrorIfTheUSerTryToEditACommentOfAnotherUser(): void
    {
        $this->comment_dao_searcher = CommentSearcherStub::withComment(
            CommentTestBuilder::aMarkdownComment('smarting meliponine')
                ->byAuthor(UserTestBuilder::buildWithId(128))
                ->build()
        );

        $result = $this->handle();

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(CommentIsNotFromCurrentUserFault::class, $result->error);
        self::assertSame(0, $this->comment_updater_dao->getUpdateCommentMethodCount());
    }

    public function testUserCannotSeeThePullRequest(): void
    {
        $this->pull_request_permission_checker = CheckUserCanAccessPullRequestStub::withException(new UserCannotReadGitRepositoryException());

        $result = $this->handle();

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(CannotAccessToPullRequestFault::class, $result->error);
        self::assertSame(0, $this->comment_updater_dao->getUpdateCommentMethodCount());
    }

    public function testItReturnsAnErrorWhenTheEditedCommentIsNotInMarkdown(): void
    {
        $this->comment_dao_searcher = CommentSearcherStub::withComment(
            CommentTestBuilder::aTextComment('smarting meliponine')->build()
        );

        $result = $this->handle();
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(CommentFormatNotAllowedFault::class, $result->error);
        self::assertSame(0, $this->comment_updater_dao->getUpdateCommentMethodCount());
    }

    public function testUpdatesTheComment(): void
    {
        $result = $this->handle();

        self::assertTrue(Result::isOk($result));
        self::assertNull($result->value);
        self::assertSame(1, $this->comment_updater_dao->getUpdateCommentMethodCount());
    }
}
