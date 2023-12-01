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

namespace Tuleap\PullRequest\REST\v1\Comment;

use Tuleap\Git\Tests\Stub\RetrieveGitRepositoryStub;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\PullRequest\Authorization\GitRepositoryNotFoundFault;
use Tuleap\PullRequest\Comment\CommentCreator;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\PullRequest\REST\v1\CommentPOSTRepresentation;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\PullRequest\Tests\Stub\CountThreadsStub;
use Tuleap\PullRequest\Tests\Stub\CreateCommentStub;
use Tuleap\PullRequest\Tests\Stub\ParentCommentSearcherStub;
use Tuleap\PullRequest\Tests\Stub\ThreadColorUpdaterStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ContentInterpretorStub;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Test\Stubs\ExtractAndSaveCrossReferencesStub;

final class POSTCommentHandlerTest extends TestCase
{
    private const INSERTED_ID    = 59;
    private const CONTENT        = 'interinsurance';
    private const PARENT_ID      = 58;
    private const POST_TIMESTAMP = 1651725436;
    private const AUTHOR_ID      = 658;
    private string $format;
    private ?int $parent_id;
    private ParentCommentSearcherStub $parent_comment_searcher;
    private RetrieveGitRepositoryStub $repository_retriever;

    protected function setUp(): void
    {
        $this->format    = TimelineComment::FORMAT_TEXT;
        $this->parent_id = self::PARENT_ID;

        $project        = ProjectTestBuilder::aProject()->build();
        $git_repository = new \GitRepository();
        $git_repository->setProject($project);

        $this->parent_comment_searcher = ParentCommentSearcherStub::withNotFound();
        $this->repository_retriever    = RetrieveGitRepositoryStub::withGitRepository($git_repository);
    }

    private function handle(): Ok|Err
    {
        $user         = UserTestBuilder::buildWithId(self::AUTHOR_ID);
        $post_date    = new \DateTimeImmutable('@' . self::POST_TIMESTAMP);
        $pull_request = PullRequestTestBuilder::aPullRequestInReview()->build();

        $comment_data = new CommentPOSTRepresentation(self::CONTENT, $this->format, $this->parent_id);

        $handler = new POSTCommentHandler(
            $this->repository_retriever,
            new CommentCreator(
                CreateCommentStub::withInsertedId(self::INSERTED_ID),
                ExtractAndSaveCrossReferencesStub::withCallCount(),
                EventDispatcherStub::withIdentityCallback(),
                new ThreadCommentColorRetriever(
                    CountThreadsStub::withNumberOfThreads(1),
                    $this->parent_comment_searcher
                ),
                new ThreadCommentColorAssigner($this->parent_comment_searcher, ThreadColorUpdaterStub::withCallCount())
            ),
            new CommentRepresentationBuilder(\Codendi_HTMLPurifier::instance(), ContentInterpretorStub::build())
        );
        return $handler->handle($comment_data, $user, $post_date, $pull_request);
    }

    public function testItCreatesANewCommentAndReturnsItsRepresentation(): void
    {
        $thread_color                  = ThreadColors::TLP_COLORS[1];
        $this->parent_comment_searcher = ParentCommentSearcherStub::withParent(self::PARENT_ID, 0, $thread_color);

        $result = $this->handle();

        self::assertTrue(Result::isOk($result));
        $representation = $result->value;
        self::assertInstanceOf(CommentRepresentation::class, $representation);
        self::assertSame(self::INSERTED_ID, $representation->id);
        self::assertSame(self::CONTENT, $representation->raw_content);
        self::assertNotEmpty($representation->content);
        self::assertSame(TimelineComment::FORMAT_TEXT, $representation->format);
        self::assertSame(self::PARENT_ID, $representation->parent_id);
        self::assertSame($thread_color, $representation->color);
        self::assertNotEmpty($representation->post_date);
        self::assertNull($representation->last_edition_date);
    }

    public function testWhenGivenEmptyFormatItDefaultsToMarkdown(): void
    {
        $this->format    = '';
        $this->parent_id = 0;

        $result = $this->handle();

        self::assertTrue(Result::isOk($result));
        self::assertSame(TimelineComment::FORMAT_MARKDOWN, $result->value->format);
    }

    public function testWhenGivenNullParentIdItDefaultsToZero(): void
    {
        $this->parent_id = null;

        $result = $this->handle();

        self::assertTrue(Result::isOk($result));
        self::assertSame(0, $result->value->parent_id);
    }

    public function testItReturnsErrWhenSourceGitRepositoryIsNotFound(): void
    {
        $this->repository_retriever = RetrieveGitRepositoryStub::withoutGitRepository();

        $result = $this->handle();
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(GitRepositoryNotFoundFault::class, $result->error);
    }
}
