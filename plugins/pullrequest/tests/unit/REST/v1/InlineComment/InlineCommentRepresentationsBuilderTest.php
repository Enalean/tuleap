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

use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\REST\v1\PullRequestInlineCommentRepresentation;
use Tuleap\PullRequest\Tests\Builders\InlineCommentTestBuilder;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\PullRequest\Tests\Stub\SearchInlineCommentsOnFileStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ContentInterpretorStub;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;

final class InlineCommentRepresentationsBuilderTest extends TestCase
{
    private const COMMENTS_AUTHOR_ID = 190;
    private const FILE_PATH          = 'path/to/file.php';
    private const FIRST_COMMENT_ID   = 761;
    private const SECOND_COMMENT_ID  = 762;
    private SearchInlineCommentsOnFileStub $comments_searcher;
    private RetrieveUserByIdStub $user_retriever;
    private PullRequest $pull_request;

    protected function setUp(): void
    {
        $comments_author    = UserTestBuilder::buildWithId(self::COMMENTS_AUTHOR_ID);
        $this->pull_request = PullRequestTestBuilder::aPullRequestInReview()->build();

        $first_comment  = InlineCommentTestBuilder::aMarkdownComment('singed fosie')
            ->withId(self::FIRST_COMMENT_ID)
            ->onFile(self::FILE_PATH)
            ->onPullRequest($this->pull_request)
            ->byAuthor($comments_author)
            ->build();
        $second_comment = InlineCommentTestBuilder::aMarkdownComment('peracute handkerchiefful')
            ->withId(self::SECOND_COMMENT_ID)
            ->onFile(self::FILE_PATH)
            ->onPullRequest($this->pull_request)
            ->byAuthor($comments_author)
            ->build();

        $this->comments_searcher = SearchInlineCommentsOnFileStub::withComments($first_comment, $second_comment);
        $this->user_retriever    = RetrieveUserByIdStub::withUser($comments_author);
    }

    /** @return PullRequestInlineCommentRepresentation[] */
    private function build(): array
    {
        $project_id = 147;

        $builder = new InlineCommentRepresentationsBuilder(
            $this->comments_searcher,
            $this->user_retriever,
            \Codendi_HTMLPurifier::instance(),
            ContentInterpretorStub::build(),
        );
        return $builder->getForFile($this->pull_request, self::FILE_PATH, $project_id);
    }

    public function testItBuildsEmptyCollection(): void
    {
        $this->comments_searcher = SearchInlineCommentsOnFileStub::withoutComments();

        self::assertEmpty($this->build());
    }

    public function testItBuildsAListOfCommentRepresentations(): void
    {
        $representations = $this->build();
        self::assertCount(2, $representations);
        [$first_representation, $second_representation] = $representations;
        self::assertSame(self::FIRST_COMMENT_ID, $first_representation->id);
        self::assertSame(self::FILE_PATH, $first_representation->file_path);
        self::assertSame(self::COMMENTS_AUTHOR_ID, $first_representation->user->id);
        self::assertSame(self::SECOND_COMMENT_ID, $second_representation->id);
        self::assertSame(self::FILE_PATH, $second_representation->file_path);
        self::assertSame(self::COMMENTS_AUTHOR_ID, $second_representation->user->id);
    }

    public function testItThrowsWhenDatabaseIsLikelyCorruptBecauseCommentAuthorCannotBeFound(): void
    {
        $this->user_retriever = RetrieveUserByIdStub::withNoUser();
        $this->expectException(\Exception::class);
        $this->build();
    }
}
