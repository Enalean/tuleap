<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Tests\PullRequest\REST\v1\Comment;

use Tuleap\Git\Tests\Stub\RetrieveGitRepositoryStub;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\PullRequest\Authorization\CannotAccessToPullRequestFault;
use Tuleap\PullRequest\Comment\CommentFormatNotAllowedFault;
use Tuleap\PullRequest\Comment\CommentIsNotFromCurrentUserFault;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\InlineComment\InlineCommentNotFoundFault;
use Tuleap\PullRequest\InlineComment\InlineCommentRetriever;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\PullRequest\REST\v1\Comment\InlineCommentPATCHRepresentation;
use Tuleap\PullRequest\REST\v1\Comment\PATCHInlineCommentHandler;
use Tuleap\PullRequest\Tests\Builders\InlineCommentTestBuilder;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\PullRequest\Tests\Stub\CheckUserCanAccessPullRequestStub;
use Tuleap\PullRequest\Tests\Stub\InlineCommentSaverStub;
use Tuleap\PullRequest\Tests\Stub\InlineCommentSearcherStub;
use Tuleap\PullRequest\Tests\Stub\SearchPullRequestStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ExtractAndSaveCrossReferencesStub;

final class PATCHInlineCommentHandlerTest extends TestCase
{
    private const INLINE_COMMENT_ID = 49;
    private string $updated_content;
    private InlineCommentSearcherStub $comment_searcher;
    private PullRequest $pull_request;
    private \PFUser $comment_author;
    private CheckUserCanAccessPullRequestStub $permission_checker;
    private InlineCommentSaverStub $comment_saver;
    private ExtractAndSaveCrossReferencesStub $cross_references_saver;

    protected function setUp(): void
    {
        $this->updated_content = 'animastical zebrawood';
        $this->comment_author  = UserTestBuilder::buildWithId(150);
        $this->pull_request    = PullRequestTestBuilder::aPullRequestInReview()->build();
        $inline_comment        = InlineCommentTestBuilder::aMarkdownComment('initial content')
            ->withId(self::INLINE_COMMENT_ID)
            ->onPullRequest($this->pull_request)
            ->byAuthor($this->comment_author)
            ->build();

        $this->comment_searcher       = InlineCommentSearcherStub::withComment($inline_comment);
        $this->permission_checker     = CheckUserCanAccessPullRequestStub::withAllowed();
        $this->comment_saver          = InlineCommentSaverStub::withCallCount();
        $this->cross_references_saver = ExtractAndSaveCrossReferencesStub::withCallCount();
    }

    /**
     * @return Ok<null> | Err<Fault>
     */
    private function handle(): Ok|Err
    {
        $handler = new PATCHInlineCommentHandler(
            new InlineCommentRetriever($this->comment_searcher),
            new PullRequestRetriever(SearchPullRequestStub::withPullRequest($this->pull_request)),
            $this->permission_checker,
            $this->comment_saver,
            RetrieveGitRepositoryStub::withGitRepository(new \GitRepository()),
            $this->cross_references_saver
        );
        return $handler->handle(
            $this->comment_author,
            self::INLINE_COMMENT_ID,
            new InlineCommentPATCHRepresentation($this->updated_content)
        );
    }

    public function testItUpdatesTheComment(): void
    {
        $result = $this->handle();
        self::assertTrue(Result::isOk($result));
        self::assertNull($result->value);
        self::assertSame(1, $this->comment_saver->getCallCount());
        $updated_comment = $this->comment_saver->getLastArgument();
        self::assertSame($this->updated_content, $updated_comment?->getContent());
        self::assertSame(1, $this->cross_references_saver->getCallCount());
    }

    public function testItReturnsAnErrWhenInlineCommentCantBeFound(): void
    {
        $this->comment_searcher = InlineCommentSearcherStub::withNoComment();

        $result = $this->handle();
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(InlineCommentNotFoundFault::class, $result->error);
        self::assertSame(0, $this->comment_saver->getCallCount());
    }

    public function testItReturnsAnErrWhenTheInlineCommentIsNotInMarkdown(): void
    {
        $this->comment_searcher = InlineCommentSearcherStub::withComment(
            InlineCommentTestBuilder::aTextComment('text comment')->build()
        );

        $result = $this->handle();
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(CommentFormatNotAllowedFault::class, $result->error);
        self::assertSame(0, $this->comment_saver->getCallCount());
    }

    public function testItReturnsAnErrWhenUserTriesToModifyTheCommentOfAnotherUser(): void
    {
        $this->comment_searcher = InlineCommentSearcherStub::withComment(
            InlineCommentTestBuilder::aMarkdownComment('initial content')
                ->withId(self::INLINE_COMMENT_ID)
                ->byAuthor(UserTestBuilder::buildWithId(132))
                ->build()
        );

        $result = $this->handle();
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(CommentIsNotFromCurrentUserFault::class, $result->error);
        self::assertSame(0, $this->comment_saver->getCallCount());
    }

    public static function generateExceptions(): iterable
    {
        yield 'The destination Git repository of the pull request of the comment is not found' => [
            new \GitRepoNotFoundException(),
        ];
        yield 'User does not have access to the project that hosts the destination Git repository of the pull request of the comment' => [
            new \Project_AccessPrivateException(),
        ];
        yield 'User does not have access to the destination Git repository of the pull request of the comment' => [
            new UserCannotReadGitRepositoryException(),
        ];
    }

    /**
     * @dataProvider generateExceptions
     */
    public function testItReturnsAnErrWhenUserIsNotAllowedToSeeThePullRequest(\Throwable $throwable): void
    {
        $this->permission_checker = CheckUserCanAccessPullRequestStub::withException($throwable);

        $result = $this->handle();
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(CannotAccessToPullRequestFault::class, $result->error);
        self::assertSame(0, $this->comment_saver->getCallCount());
    }
}
