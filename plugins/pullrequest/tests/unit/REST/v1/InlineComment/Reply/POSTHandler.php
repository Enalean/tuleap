<?php
/**
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1\InlineComment\Reply;

use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Git\Tests\Stub\RetrieveGitRepositoryStub;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\PullRequest\Authorization\CannotAccessToPullRequestFault;
use Tuleap\PullRequest\Authorization\GitRepositoryNotFoundFault;
use Tuleap\PullRequest\Comment\ThreadCommentColorAssigner;
use Tuleap\PullRequest\Comment\ThreadCommentColorRetriever;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\InlineComment\InlineCommentCreator;
use Tuleap\PullRequest\InlineComment\InlineCommentNotFoundFault;
use Tuleap\PullRequest\InlineComment\InlineCommentRetriever;
use Tuleap\PullRequest\InlineComment\RootInlineCommentHasAParentFault;
use Tuleap\PullRequest\PullRequest\REST\v1\InlineComment\Reply\InlineCommentReplyPOSTRepresentation;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\PullRequest\PullRequestNotFoundFault;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\PullRequest\REST\v1\InlineComment\SingleRepresentationBuilder;
use Tuleap\PullRequest\Tests\Builders\InlineCommentTestBuilder;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\PullRequest\Tests\Stub\CheckUserCanAccessPullRequestStub;
use Tuleap\PullRequest\Tests\Stub\CountThreadsStub;
use Tuleap\PullRequest\Tests\Stub\CreateInlineCommentStub;
use Tuleap\PullRequest\Tests\Stub\InlineCommentSearcherStub;
use Tuleap\PullRequest\Tests\Stub\ParentCommentSearcherStub;
use Tuleap\PullRequest\Tests\Stub\SearchPullRequestStub;
use Tuleap\PullRequest\Tests\Stub\ThreadColorUpdaterStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ContentInterpretorStub;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Test\Stubs\ExtractAndSaveCrossReferencesStub;
use Tuleap\User\Avatar\AvatarHashDao;
use Tuleap\User\Avatar\ComputeAvatarHash;
use Tuleap\User\Avatar\UserAvatarUrlProvider;

final class POSTHandler extends TestCase
{
    private const ROOT_COMMENT_ID = 80;

    private \PFUser $reply_author;
    private InlineCommentReplyPOSTRepresentation $reply_data;

    #[\Override]
    protected function setUp(): void
    {
        $this->reply_author = UserTestBuilder::anActiveUser()->build();
        $this->reply_data   = new InlineCommentReplyPOSTRepresentation('This is **my** reply', TimelineComment::FORMAT_MARKDOWN);
    }

    public function testItReturnsAFaultWhenTheRootCommentIdIsNotFound(): void
    {
        $result = $this->handle(
            InlineCommentSearcherStub::withNoComment(),
            SearchPullRequestStub::withNoRow(),
            CheckUserCanAccessPullRequestStub::withAllowed(),
            RetrieveGitRepositoryStub::withoutGitRepository(),
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(InlineCommentNotFoundFault::class, $result->error);
    }

    public function testItReturnsAFaultWhenTheRootCommentIdIsNotARootComment(): void
    {
        $result = $this->handle(
            InlineCommentSearcherStub::withComment(
                InlineCommentTestBuilder::aMarkdownComment('suggestion: go raise goats in the mountain.')->childOf(2041)->build()
            ),
            SearchPullRequestStub::withNoRow(),
            CheckUserCanAccessPullRequestStub::withAllowed(),
            RetrieveGitRepositoryStub::withoutGitRepository(),
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(RootInlineCommentHasAParentFault::class, $result->error);
    }

    public function testItReturnsAFaultWhenThePullRequestIsNotFound(): void
    {
        $result = $this->handle(
            InlineCommentSearcherStub::withComment(
                InlineCommentTestBuilder::aMarkdownComment('suggestion: go raise goats in the mountain.')->build()
            ),
            SearchPullRequestStub::withNoRow(),
            CheckUserCanAccessPullRequestStub::withAllowed(),
            RetrieveGitRepositoryStub::withoutGitRepository(),
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(PullRequestNotFoundFault::class, $result->error);
    }

    public static function generateDestinationGitExceptions(): iterable
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

    #[\PHPUnit\Framework\Attributes\DataProvider('generateDestinationGitExceptions')]
    public function testItReturnsAFaultWhenTheUserCannotAccessThePullRequest(\Throwable $throwable): void
    {
        $result = $this->handle(
            InlineCommentSearcherStub::withComment(
                InlineCommentTestBuilder::aMarkdownComment('suggestion: go raise goats in the mountain.')->build()
            ),
            SearchPullRequestStub::withAtLeastOnePullRequest(
                PullRequestTestBuilder::aPullRequestInReview()->build()
            ),
            CheckUserCanAccessPullRequestStub::withException($throwable),
            RetrieveGitRepositoryStub::withoutGitRepository(),
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(CannotAccessToPullRequestFault::class, $result->error);
    }

    public function testItReturnsAFaultWhenTheGitRepositoryIsNotFound(): void
    {
        $result = $this->handle(
            InlineCommentSearcherStub::withComment(
                InlineCommentTestBuilder::aMarkdownComment('suggestion: go raise goats in the mountain.')->build()
            ),
            SearchPullRequestStub::withAtLeastOnePullRequest(
                PullRequestTestBuilder::aPullRequestInReview()->build()
            ),
            CheckUserCanAccessPullRequestStub::withAllowed(),
            RetrieveGitRepositoryStub::withoutGitRepository(),
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(GitRepositoryNotFoundFault::class, $result->error);
    }

    public function testHappyPath(): void
    {
        $root_comment = InlineCommentTestBuilder::aMarkdownComment('suggestion: go raise goats in the mountain.')->withId(self::ROOT_COMMENT_ID)->build();

        $result = $this->handle(
            InlineCommentSearcherStub::withComment(
                $root_comment
            ),
            SearchPullRequestStub::withAtLeastOnePullRequest(
                PullRequestTestBuilder::aPullRequestInReview()->build()
            ),
            CheckUserCanAccessPullRequestStub::withAllowed(),
            RetrieveGitRepositoryStub::withGitRepository(
                GitRepositoryTestBuilder::aProjectRepository()->build()
            ),
        );

        self::assertTrue(Result::isOk($result));

        self::assertSame($root_comment->getId() + 1, $result->value->id);
        self::assertSame($root_comment->getId(), $result->value->parent_id);
        self::assertSame($this->reply_data->content, $result->value->content);
        self::assertSame($this->reply_data->format, $result->value->format);
        self::assertSame($this->reply_author->getId(), $result->value->user->id);
        self::assertSame($root_comment->getFilePath(), $result->value->file_path);
        self::assertSame($root_comment->getUnidiffOffset(), $result->value->unidiff_offset);
        self::assertSame($root_comment->getPosition(), $result->value->position);
    }

    private function handle(
        InlineCommentSearcherStub $inline_comment_searcher_stub,
        SearchPullRequestStub $search_pull_request_stub,
        CheckUserCanAccessPullRequestStub $check_user_can_access_pull_request_stub,
        RetrieveGitRepositoryStub $retrieve_git_repository_stub,
    ): Ok|Err {
        $handler = new \Tuleap\PullRequest\PullRequest\REST\v1\InlineComment\Reply\POSTHandler(
            new InlineCommentRetriever($inline_comment_searcher_stub),
            new PullRequestRetriever($search_pull_request_stub),
            $check_user_can_access_pull_request_stub,
            $retrieve_git_repository_stub,
            new SingleRepresentationBuilder(
                \Codendi_HTMLPurifier::instance(),
                ContentInterpretorStub::withInterpretedText('')
            ),
            new InlineCommentCreator(
                CreateInlineCommentStub::withInsertedId(self::ROOT_COMMENT_ID + 1),
                ExtractAndSaveCrossReferencesStub::withCallCount(),
                EventDispatcherStub::withIdentityCallback(),
                new ThreadCommentColorRetriever(
                    CountThreadsStub::withNumberOfThreads(0),
                    ParentCommentSearcherStub::withNotFound()
                ),
                new ThreadCommentColorAssigner(
                    ParentCommentSearcherStub::withNotFound(),
                    ThreadColorUpdaterStub::withCallCount()
                )
            ),
            new UserAvatarUrlProvider(new AvatarHashDao(), new ComputeAvatarHash()),
        );

        return $handler->handle(
            self::ROOT_COMMENT_ID,
            $this->reply_data,
            $this->reply_author,
            new \DateTimeImmutable(),
        );
    }
}
