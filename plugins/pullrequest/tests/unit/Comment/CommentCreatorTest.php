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

namespace Tuleap\PullRequest\Comment;

use Tuleap\PullRequest\Comment\Notification\PullRequestNewCommentEvent;
use Tuleap\PullRequest\Notification\EventSubjectToNotification;
use Tuleap\PullRequest\REST\v1\Comment\ThreadCommentColorAssigner;
use Tuleap\PullRequest\REST\v1\Comment\ThreadCommentColorRetriever;
use Tuleap\PullRequest\Tests\Builders\CommentTestBuilder;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\PullRequest\Tests\Stub\CountThreadsStub;
use Tuleap\PullRequest\Tests\Stub\CreateCommentStub;
use Tuleap\PullRequest\Tests\Stub\ParentCommentSearcherStub;
use Tuleap\PullRequest\Tests\Stub\ThreadColorUpdaterStub;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Test\Stubs\ExtractAndSaveCrossReferencesStub;

final class CommentCreatorTest extends TestCase
{
    private const INSERTED_ID = 57;
    private const PARENT_ID   = 46;
    private ExtractAndSaveCrossReferencesStub $reference_manager;
    private EventDispatcherStub $event_dispatcher;
    private CountThreadsStub $thread_counter;
    private ParentCommentSearcherStub $parent_comment_searcher;
    private ThreadColorUpdaterStub $thread_color_updater;

    protected function setUp(): void
    {
        $this->reference_manager       = ExtractAndSaveCrossReferencesStub::withCallCount();
        $this->event_dispatcher        = EventDispatcherStub::withIdentityCallback();
        $this->thread_counter          = CountThreadsStub::withNumberOfThreads(0);
        $this->parent_comment_searcher = ParentCommentSearcherStub::withParent(self::PARENT_ID, 0, '');
        $this->thread_color_updater    = ThreadColorUpdaterStub::withCallCount();
    }

    private function create(): Comment
    {
        $pull_request = PullRequestTestBuilder::aPullRequestInReview()->build();
        $new_comment  = CommentTestBuilder::aMarkdownComment('creatureless cladodontid')
            ->onPullRequest($pull_request)
            ->childOf(self::PARENT_ID)
            ->build();

        $creator = new CommentCreator(
            CreateCommentStub::withInsertedId(self::INSERTED_ID),
            $this->reference_manager,
            $this->event_dispatcher,
            new ThreadCommentColorRetriever($this->thread_counter, $this->parent_comment_searcher),
            new ThreadCommentColorAssigner($this->parent_comment_searcher, $this->thread_color_updater)
        );
        return $creator->create($new_comment, 111);
    }

    public function testNewCommentCanBeCreated(): void
    {
        $dispatched_event       = null;
        $this->event_dispatcher = EventDispatcherStub::withCallback(
            static function (EventSubjectToNotification $event) use (&$dispatched_event) {
                $dispatched_event = $event;
                return $event;
            }
        );

        $comment = $this->create();
        self::assertSame(self::INSERTED_ID, $comment->getId());
        self::assertSame(1, $this->reference_manager->getCallCount());
        self::assertInstanceOf(PullRequestNewCommentEvent::class, $dispatched_event);
        self::assertSame(1, $this->thread_color_updater->getCallCount());
    }
}
