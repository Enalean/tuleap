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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReferenceManager;
use Tuleap\PullRequest\Comment\ThreadCommentDao;
use Tuleap\PullRequest\InlineComment\Notification\PullRequestNewInlineCommentEvent;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\REST\v1\Comment\ThreadCommentColorAssigner;
use Tuleap\PullRequest\REST\v1\Comment\ThreadCommentColorRetriever;
use Tuleap\PullRequest\REST\v1\PullRequestInlineCommentPOSTRepresentation;

final class InlineCommentCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testNewInlineCommentCanBeCreated(): void
    {
        $dao                = $this->createMock(Dao::class);
        $reference_manager  = \Mockery::mock(ReferenceManager::class);
        $event_dispatcher   = \Mockery::mock(EventDispatcherInterface::class);
        $thread_comment_dao = $this->createMock(ThreadCommentDao::class);
        $thread_comment_dao->method('searchAllThreadByPullRequestId')->willReturn([]);
        $dao->method('searchByCommentID')->willReturn(['parent_id' => 0, "id" => 1]);
        $color_retriever = new ThreadCommentColorRetriever($thread_comment_dao);
        $color_assigner  = new ThreadCommentColorAssigner($dao, $dao);

        $creator = new InlineCommentCreator($dao, $reference_manager, $event_dispatcher, $color_retriever, $color_assigner);

        $pull_request = \Mockery::mock(PullRequest::class);
        $pull_request->shouldReceive('getId')->andReturn(12);
        $user = \Mockery::mock(PFUser::class);
        $user->shouldReceive('getId')->andReturn(102);
        $representation                 = new PullRequestInlineCommentPOSTRepresentation();
        $representation->file_path      = "/tmp";
        $representation->content        = "stuff";
        $representation->position       = 2;
        $representation->unidiff_offset = 10;
        $representation->parent_id      = 1;

        $inserted_id = 47;

        $dao->expects(self::once())->method('insert')->willReturn($inserted_id);
        $dao->expects(self::once())->method('setThreadColor');
        $reference_manager->shouldReceive('extractCrossRef')->once();
        $event_dispatcher->shouldReceive('dispatch')->with(\Mockery::type(PullRequestNewInlineCommentEvent::class))->once();

        $inline_comment = $creator->insert(
            $pull_request,
            $user,
            $representation,
            10,
            1001
        );
        $this->assertEquals(InsertedInlineComment::build($inserted_id, "graffiti-yellow"), $inline_comment);
    }
}
