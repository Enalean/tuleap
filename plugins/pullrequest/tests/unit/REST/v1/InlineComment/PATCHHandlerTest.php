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

namespace Tuleap\PullRequest\REST\v1\InlineComment;

use Tuleap\Git\Tests\Stub\RetrieveGitRepositoryStub;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\PullRequest\Authorization\CannotAccessToPullRequestFault;
use Tuleap\PullRequest\Authorization\GitRepositoryNotFoundFault;
use Tuleap\PullRequest\Comment\CommentFormatNotAllowedFault;
use Tuleap\PullRequest\Comment\CommentIsNotFromCurrentUserFault;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\InlineComment\InlineCommentNotFoundFault;
use Tuleap\PullRequest\InlineComment\InlineCommentRetriever;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\PullRequest\Tests\Builders\InlineCommentTestBuilder;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\PullRequest\Tests\Stub\CheckUserCanAccessPullRequestStub;
use Tuleap\PullRequest\Tests\Stub\InlineCommentSaverStub;
use Tuleap\PullRequest\Tests\Stub\InlineCommentSearcherStub;
use Tuleap\PullRequest\Tests\Stub\SearchPullRequestStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ContentInterpretorStub;
use Tuleap\Test\Stubs\ExtractAndSaveCrossReferencesStub;

final class PATCHHandlerTest extends TestCase
{
    private const INLINE_COMMENT_ID = 49;
    private const UPDATED_CONTENT   = 'animastical zebrawood';
    private InlineCommentSearcherStub $comment_searcher;
    private PullRequest $pull_request;
    private \PFUser $comment_author;
    private \DateTimeImmutable $comment_edition_date;
    private CheckUserCanAccessPullRequestStub $permission_checker;
    private RetrieveGitRepositoryStub $repository_retriever;
    private InlineCommentSaverStub $comment_saver;
    private ExtractAndSaveCrossReferencesStub $cross_references_saver;

    protected function setUp(): void
    {
        $this->comment_author       = UserTestBuilder::buildWithId(150);
        $this->pull_request         = PullRequestTestBuilder::aPullRequestInReview()->build();
        $inline_comment             = InlineCommentTestBuilder::aMarkdownComment('initial content')
            ->withId(self::INLINE_COMMENT_ID)
            ->onPullRequest($this->pull_request)
            ->byAuthor($this->comment_author)
            ->build();
        $this->comment_edition_date = new \DateTimeImmutable('@1420598064');

        $this->comment_searcher       = InlineCommentSearcherStub::withComment($inline_comment);
        $this->permission_checker     = CheckUserCanAccessPullRequestStub::withAllowed();
        $this->comment_saver          = InlineCommentSaverStub::withCallCount();
        $this->cross_references_saver = ExtractAndSaveCrossReferencesStub::withCallCount();

        $git_repository = new \GitRepository();
        $git_repository->setProject(ProjectTestBuilder::aProject()->withId(140)->build());
        $this->repository_retriever = RetrieveGitRepositoryStub::withGitRepository($git_repository);
    }

    /**
     * @return Ok<InlineCommentRepresentation> | Err<Fault>
     */
    private function handle(): Ok|Err
    {
        $handler = new PATCHHandler(
            new InlineCommentRetriever($this->comment_searcher),
            new PullRequestRetriever(SearchPullRequestStub::withPullRequest($this->pull_request)),
            $this->permission_checker,
            $this->comment_saver,
            $this->repository_retriever,
            $this->cross_references_saver,
            new SingleRepresentationBuilder(\Codendi_HTMLPurifier::instance(), ContentInterpretorStub::build())
        );
        return $handler->handle(
            $this->comment_author,
            self::INLINE_COMMENT_ID,
            new InlineCommentPATCHRepresentation(self::UPDATED_CONTENT),
            $this->comment_edition_date
        );
    }

    public function testItReturnsARepresentationOfTheUpdatedComment(): void
    {
        $result = $this->handle();
        self::assertTrue(Result::isOk($result));
        $representation = $result->value;
        assert($representation instanceof InlineCommentRepresentation);
        self::assertSame(self::INLINE_COMMENT_ID, $representation->id);
        self::assertSame(self::UPDATED_CONTENT, $representation->raw_content);
        self::assertNotNull($representation->last_edition_date);

        self::assertSame(1, $this->comment_saver->getCallCount());
        $updated_comment = $this->comment_saver->getLastArgument();
        self::assertSame(self::UPDATED_CONTENT, $updated_comment?->getContent());
        self::assertSame(
            $this->comment_edition_date->getTimestamp(),
            $updated_comment?->getLastEditionDate()->unwrapOr(0)
        );
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

    /**
     * @dataProvider generateDestinationGitExceptions
     */
    public function testItReturnsAnErrWhenUserIsNotAllowedToSeeThePullRequest(\Throwable $throwable): void
    {
        $this->permission_checker = CheckUserCanAccessPullRequestStub::withException($throwable);

        $result = $this->handle();
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(CannotAccessToPullRequestFault::class, $result->error);
        self::assertSame(0, $this->comment_saver->getCallCount());
    }

    public function testItReturnsAnErrWhenSourceGitRepositoryIsNotFound(): void
    {
        $this->repository_retriever = RetrieveGitRepositoryStub::withoutGitRepository();

        $result = $this->handle();
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(GitRepositoryNotFoundFault::class, $result->error);
        self::assertSame(0, $this->comment_saver->getCallCount());
    }
}
