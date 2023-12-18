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

use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Git\Tests\Stub\RetrieveGitRepositoryStub;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\PullRequest\Authorization\GitRepositoryNotFoundFault;
use Tuleap\PullRequest\InlineComment\InlineCommentCreator;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\PullRequest\REST\v1\Comment\ThreadColors;
use Tuleap\PullRequest\REST\v1\Comment\ThreadCommentColorAssigner;
use Tuleap\PullRequest\REST\v1\Comment\ThreadCommentColorRetriever;
use Tuleap\PullRequest\REST\v1\PullRequestInlineCommentPOSTRepresentation;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\PullRequest\Tests\Stub\CountThreadsStub;
use Tuleap\PullRequest\Tests\Stub\CreateInlineCommentStub;
use Tuleap\PullRequest\Tests\Stub\ParentCommentSearcherStub;
use Tuleap\PullRequest\Tests\Stub\ThreadColorUpdaterStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ContentInterpretorStub;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Test\Stubs\ExtractAndSaveCrossReferencesStub;

final class POSTHandlerTest extends TestCase
{
    private const INSERTED_ID    = 57;
    private const CONTENT        = 'caciocavallo';
    private const FILE_PATH      = 'path/to/file.php';
    private const UNIDIFF_OFFSET = 23;
    private const POSITION       = 'right';
    private const PARENT_ID      = 43;
    private const POST_TIMESTAMP = 1509159123;
    private const AUTHOR_ID      = 305;
    private string $format;
    private ?int $parent_id;
    private ParentCommentSearcherStub $parent_comment_searcher;
    private RetrieveGitRepositoryStub $repository_retriever;

    protected function setUp(): void
    {
        $this->format    = TimelineComment::FORMAT_TEXT;
        $this->parent_id = self::PARENT_ID;

        $git_repository = GitRepositoryTestBuilder::aProjectRepository()->build();

        $this->parent_comment_searcher = ParentCommentSearcherStub::withNotFound();
        $this->repository_retriever    = RetrieveGitRepositoryStub::withGitRepository($git_repository);
    }

    /**
     * @return Ok<InlineCommentRepresentation> | Err<Fault>
     */
    private function handle(): Ok|Err
    {
        $user         = UserTestBuilder::buildWithId(self::AUTHOR_ID);
        $post_date    = new \DateTimeImmutable('@' . self::POST_TIMESTAMP);
        $pull_request = PullRequestTestBuilder::aPullRequestInReview()->build();

        $comment_data = PullRequestInlineCommentPOSTRepresentation::build(
            self::CONTENT,
            self::FILE_PATH,
            self::UNIDIFF_OFFSET,
            self::POSITION,
            $this->parent_id,
            $this->format
        );

        $handler = new POSTHandler(
            $this->repository_retriever,
            new InlineCommentCreator(
                CreateInlineCommentStub::withInsertedId(self::INSERTED_ID),
                ExtractAndSaveCrossReferencesStub::withCallCount(),
                EventDispatcherStub::withIdentityCallback(),
                new ThreadCommentColorRetriever(
                    CountThreadsStub::withNumberOfThreads(0),
                    $this->parent_comment_searcher
                ),
                new ThreadCommentColorAssigner($this->parent_comment_searcher, ThreadColorUpdaterStub::withCallCount())
            ),
            new SingleRepresentationBuilder(
                \Codendi_HTMLPurifier::instance(),
                ContentInterpretorStub::withInterpretedText('')
            )
        );
        return $handler->handle($comment_data, $user, $post_date, $pull_request);
    }

    public function testItCreatesANewInlineCommentAndReturnsItsRepresentation(): void
    {
        $thread_color                  = ThreadColors::TLP_COLORS[0];
        $this->parent_comment_searcher = ParentCommentSearcherStub::withParent(self::PARENT_ID, 0, $thread_color);

        $result = $this->handle();

        self::assertTrue(Result::isOk($result));
        $representation = $result->value;
        self::assertInstanceOf(InlineCommentRepresentation::class, $representation);
        self::assertSame(self::INSERTED_ID, $representation->id);
        self::assertSame(self::FILE_PATH, $representation->file_path);
        self::assertSame(self::UNIDIFF_OFFSET, $representation->unidiff_offset);
        self::assertSame(self::POSITION, $representation->position);
        self::assertSame(self::CONTENT, $representation->raw_content);
        self::assertNotEmpty($representation->content);
        self::assertSame(TimelineComment::FORMAT_TEXT, $representation->format);
        self::assertSame(self::PARENT_ID, $representation->parent_id);
        self::assertSame($thread_color, $representation->color);
        self::assertNotEmpty($representation->post_date);
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
