<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\InlineComment;

use Psr\EventDispatcher\EventDispatcherInterface;
use ReferenceManager;
use Tuleap\PullRequest\Comment\Comment;
use Tuleap\PullRequest\Comment\ParentCommentSearcher;
use Tuleap\PullRequest\Comment\ThreadCommentDao;
use Tuleap\PullRequest\InlineComment\Notification\PullRequestNewInlineCommentEvent;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\REST\v1\Comment\ThreadCommentColorAssigner;
use Tuleap\PullRequest\REST\v1\Comment\ThreadCommentColorRetriever;
use Tuleap\PullRequest\REST\v1\PullRequestInlineCommentPOSTRepresentation;
use Tuleap\Test\Builders\UserTestBuilder;

final class InlineCommentCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testNewInlineCommentCanBeCreatedInTextFormat(): void
    {
        $dao                     = $this->createMock(Dao::class);
        $reference_manager       = $this->createMock(ReferenceManager::class);
        $event_dispatcher        = $this->createMock(EventDispatcherInterface::class);
        $thread_comment_dao      = $this->createMock(ThreadCommentDao::class);
        $parent_comment_searcher = $this->createMock(ParentCommentSearcher::class);
        $thread_comment_dao->method('searchAllThreadByPullRequestId')->willReturn([]);
        $dao->method('searchByCommentID')->willReturn(['parent_id' => 0, "id" => 1, 'color' => ""]);
        $parent_comment_searcher->method('searchByCommentID')->willReturn(['parent_id' => 0, "id" => 1, 'color' => ""]);
        $color_retriever = new ThreadCommentColorRetriever($thread_comment_dao, $parent_comment_searcher);
        $color_assigner  = new ThreadCommentColorAssigner($dao, $dao);

        $creator = new InlineCommentCreator($dao, $reference_manager, $event_dispatcher, $color_retriever, $color_assigner);

        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getId')->willReturn(12);
        $user = UserTestBuilder::anActiveUser()->build();

        $representation = PullRequestInlineCommentPOSTRepresentation::build(
            "stuff",
            "/tmp",
            10,
            "2",
            1,
            Comment::FORMAT_TEXT
        );

        $inserted_id = 47;

        $dao->expects(self::once())->method('insert')->willReturn($inserted_id);
        $dao->expects(self::once())->method('setThreadColor');
        $reference_manager->expects(self::once())->method('extractCrossRef');
        $event_dispatcher->expects(self::once())->method('dispatch')->with(self::isInstanceOf(PullRequestNewInlineCommentEvent::class));

        $inline_comment = $creator->insert(
            $pull_request,
            $user,
            $representation,
            10,
            1001,
        );
        self::assertEquals(InsertedInlineComment::build($inserted_id, "graffiti-yellow"), $inline_comment);
    }

    public function testWhenFormatIsNotDefinedThenDefaultCommentFormatIsMarkdown(): void
    {
        $dao                     = $this->createMock(Dao::class);
        $reference_manager       = $this->createMock(ReferenceManager::class);
        $event_dispatcher        = $this->createMock(EventDispatcherInterface::class);
        $thread_comment_dao      = $this->createMock(ThreadCommentDao::class);
        $parent_comment_searcher = $this->createMock(ParentCommentSearcher::class);
        $thread_comment_dao->method('searchAllThreadByPullRequestId')->willReturn([]);
        $dao->method('searchByCommentID')->willReturn(['parent_id' => 0, "id" => 1, 'color' => ""]);
        $parent_comment_searcher->method('searchByCommentID')->willReturn(['parent_id' => 0, "id" => 1, 'color' => ""]);
        $color_retriever = new ThreadCommentColorRetriever($thread_comment_dao, $parent_comment_searcher);
        $color_assigner  = new ThreadCommentColorAssigner($dao, $dao);

        $creator = new InlineCommentCreator($dao, $reference_manager, $event_dispatcher, $color_retriever, $color_assigner);

        $pull_request    = $this->createMock(PullRequest::class);
        $pull_request_id = 12;
        $pull_request->method('getId')->willReturn($pull_request_id);
        $user = UserTestBuilder::anActiveUser()->build();

        $representation = PullRequestInlineCommentPOSTRepresentation::build(
            "stuff",
            "/tmp",
            10,
            "2",
            1,
            ""
        );

        $inserted_id = 47;
        $post_date   = 10;

        $dao->expects(self::once())->method('insert')->with(
            $pull_request_id,
            $user->getId(),
            $representation->file_path,
            $post_date,
            $representation->unidiff_offset,
            $representation->content,
            $representation->position,
            (int) $representation->parent_id,
            Comment::FORMAT_MARKDOWN
        )->willReturn($inserted_id);
        $dao->expects(self::once())->method('setThreadColor');
        $reference_manager->expects(self::once())->method('extractCrossRef');
        $event_dispatcher->expects(self::once())->method('dispatch')->with(self::isInstanceOf(PullRequestNewInlineCommentEvent::class));

        $inline_comment = $creator->insert(
            $pull_request,
            $user,
            $representation,
            $post_date,
            1001,
        );
        self::assertEquals(InsertedInlineComment::build($inserted_id, "graffiti-yellow"), $inline_comment);
    }
}
