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

namespace Tuleap\PullRequest\REST\v1\Comment;

use DateTimeImmutable;
use Luracast\Restler\RestException;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Git\Tests\Stub\RetrieveGitRepositoryStub;
use Tuleap\Markdown\CodeBlockFeatures;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\Markdown\EnhancedCodeBlockExtension;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\PullRequest\Authorization\GitRepositoryNotFoundFault;
use Tuleap\PullRequest\Comment\CommentFormatNotAllowedFault;
use Tuleap\PullRequest\Comment\CommentIsNotFromCurrentUserFault;
use Tuleap\PullRequest\Comment\CommentNotFoundFault;
use Tuleap\PullRequest\Comment\CommentRetriever;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\PullRequest\REST\v1\AccessiblePullRequestRESTRetriever;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\PullRequest\REST\v1\CommentPATCHRepresentation;
use Tuleap\PullRequest\Tests\Builders\CommentTestBuilder;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\PullRequest\Tests\Stub\CheckUserCanAccessPullRequestStub;
use Tuleap\PullRequest\Tests\Stub\CommentSearcherStub;
use Tuleap\PullRequest\Tests\Stub\CommentUpdaterStub;
use Tuleap\PullRequest\Tests\Stub\SearchPullRequestStub;
use Tuleap\REST\JsonCast;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Test\Stubs\ExtractAndSaveCrossReferencesStub;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PATCHCommentHandlerTest extends TestCase
{
    private const CURRENT_USER_ID = 105;
    private const NEW_CONTENT     = 'B35';

    private CommentPATCHRepresentation $comment_data;
    private CommentSearcherStub $comment_dao_searcher;
    private CommentUpdaterStub $comment_updater_dao;
    private SearchPullRequestStub $pull_request_dao;
    private CheckUserCanAccessPullRequestStub $pull_request_permission_checker;
    private \PFUser $comment_author;
    private RetrieveGitRepositoryStub $git_factory;
    private DateTimeImmutable $last_edition_date;
    private ExtractAndSaveCrossReferencesStub $cross_references_saver;

    protected function setUp(): void
    {
        $this->comment_data = new CommentPATCHRepresentation(self::NEW_CONTENT);

        $pull_request         = PullRequestTestBuilder::aPullRequestInReview()->build();
        $this->comment_author = UserTestBuilder::buildWithId(self::CURRENT_USER_ID);
        $comment              = CommentTestBuilder::aMarkdownComment('smarting meliponine')
            ->byAuthor($this->comment_author)
            ->onPullRequest($pull_request)
            ->build();

        $this->comment_dao_searcher            = CommentSearcherStub::withComment($comment);
        $this->comment_updater_dao             = CommentUpdaterStub::fromDefault();
        $this->pull_request_dao                = SearchPullRequestStub::withAtLeastOnePullRequest($pull_request);
        $this->pull_request_permission_checker = CheckUserCanAccessPullRequestStub::withAllowed();
        $this->cross_references_saver          = ExtractAndSaveCrossReferencesStub::withCallCount();

        $git_repository    = GitRepositoryTestBuilder::aProjectRepository()->build();
        $this->git_factory = RetrieveGitRepositoryStub::withGitRepository($git_repository);

        $this->last_edition_date = new DateTimeImmutable();
    }

    /**
     * @return Ok<CommentRepresentation>|Err<Fault>
     */
    private function handle(): Ok|Err
    {
        $purifier            = \Codendi_HTMLPurifier::instance();
        $put_handler_comment = new PATCHCommentHandler(
            new CommentRetriever($this->comment_dao_searcher),
            $this->comment_updater_dao,
            new AccessiblePullRequestRESTRetriever(new PullRequestRetriever($this->pull_request_dao), $this->pull_request_permission_checker),
            new CommentRepresentationBuilder(
                $purifier,
                CommonMarkInterpreter::build(
                    $purifier,
                    new EnhancedCodeBlockExtension(new CodeBlockFeatures())
                )
            ),
            $this->git_factory,
            $this->cross_references_saver,
            EventDispatcherStub::withIdentityCallback(),
            ProvideUserAvatarUrlStub::build(),
        );
        return $put_handler_comment->handle($this->comment_author, 1058, $this->comment_data, $this->last_edition_date);
    }

    public function testItReturnsAnErrorIfTheCommentIsNotFound(): void
    {
        $this->comment_dao_searcher = CommentSearcherStub::withNoComment();

        $result = $this->handle();

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(CommentNotFoundFault::class, $result->error);
        self::assertSame(0, $this->comment_updater_dao->getUpdateCommentMethodCount());
        self::assertSame(0, $this->cross_references_saver->getCallCount());
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
        self::assertSame(0, $this->cross_references_saver->getCallCount());
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
    public function testUserCannotSeeThePullRequest(\Throwable $throwable): void
    {
        $this->pull_request_permission_checker = CheckUserCanAccessPullRequestStub::withException($throwable);

        $this->expectException(RestException::class);
        $this->handle();

        self::assertSame(0, $this->comment_updater_dao->getUpdateCommentMethodCount());
        self::assertSame(0, $this->cross_references_saver->getCallCount());
    }

    public function testItReturnsAnErrWhenSourceGitRepositoryIsNotFound(): void
    {
        $this->git_factory = RetrieveGitRepositoryStub::withoutGitRepository();

        $result = $this->handle();
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(GitRepositoryNotFoundFault::class, $result->error);
        self::assertSame(0, $this->comment_updater_dao->getUpdateCommentMethodCount());
        self::assertSame(0, $this->cross_references_saver->getCallCount());
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
        self::assertSame(0, $this->cross_references_saver->getCallCount());
    }

    public function testUpdatesTheComment(): void
    {
        $comment_id  = 1;
        $old_content = 'old content';
        $comment     =  CommentTestBuilder::aMarkdownComment(self::NEW_CONTENT)
                ->withId($comment_id)
                    ->build();

        $this->comment_dao_searcher = CommentSearcherStub::withComment($comment);

        $result = $this->handle();

        self::assertTrue(Result::isOk($result));
        self::assertInstanceOf(CommentRepresentation::class, $result->value);
        $updated_comment = $result->value;
        self::assertSame($comment_id, $updated_comment->id);

        self::assertNotSame($old_content, $updated_comment->content);
        self::assertSame(self::NEW_CONTENT, $updated_comment->content);

        self::assertNotSame(
            $comment->getLastEditionDate()->mapOr(static fn(\DateTimeImmutable $last_edition_date) => $last_edition_date->getTimestamp(), 0),
            $updated_comment->last_edition_date
        );
        self::assertSame(JsonCast::fromDateTimeToDate($this->last_edition_date), $updated_comment->last_edition_date);

        self::assertSame(1, $this->comment_updater_dao->getUpdateCommentMethodCount());
        self::assertSame(1, $this->cross_references_saver->getCallCount());
    }
}
