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

use Tuleap\PullRequest\InlineComment\Notification\PullRequestNewInlineCommentEvent;
use Tuleap\PullRequest\Notification\EventSubjectToNotification;
use Tuleap\PullRequest\REST\v1\Comment\ThreadColors;
use Tuleap\PullRequest\REST\v1\Comment\ThreadCommentColorAssigner;
use Tuleap\PullRequest\REST\v1\Comment\ThreadCommentColorRetriever;
use Tuleap\PullRequest\Tests\Builders\NewInlineCommentTestBuilder;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\PullRequest\Tests\Stub\CreateInlineCommentStub;
use Tuleap\PullRequest\Tests\Stub\ParentCommentSearcherStub;
use Tuleap\PullRequest\Tests\Stub\CountThreadsStub;
use Tuleap\PullRequest\Tests\Stub\ThreadColorUpdaterStub;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Test\Stubs\ExtractAndSaveCrossReferencesStub;

final class InlineCommentCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const INSERTED_ID = 47;
    private const PARENT_ID   = 34;
    private ExtractAndSaveCrossReferencesStub $reference_manager;
    private CountThreadsStub $thread_counter;
    private ParentCommentSearcherStub $parent_comment_searcher;
    private ThreadColorUpdaterStub $thread_color_updater;
    private EventDispatcherStub $event_dispatcher;

    protected function setUp(): void
    {
        $this->reference_manager       = ExtractAndSaveCrossReferencesStub::withCallCount();
        $this->event_dispatcher        = EventDispatcherStub::withIdentityCallback();
        $this->thread_counter          = CountThreadsStub::withNumberOfThreads(0);
        $this->parent_comment_searcher = ParentCommentSearcherStub::withParent(self::PARENT_ID, 0, '');
        $this->thread_color_updater    = ThreadColorUpdaterStub::withCallCount();
    }

    private function create(): InsertedInlineComment
    {
        $pull_request = PullRequestTestBuilder::aPullRequestInReview()->build();
        $new_comment  = NewInlineCommentTestBuilder::aTextComment('beheadlined craver')
            ->onPullRequest($pull_request)
            ->childOf(self::PARENT_ID)
            ->build();

        $creator = new InlineCommentCreator(
            CreateInlineCommentStub::withInsertedId(self::INSERTED_ID),
            $this->reference_manager,
            $this->event_dispatcher,
            new ThreadCommentColorRetriever($this->thread_counter, $this->parent_comment_searcher),
            new ThreadCommentColorAssigner($this->parent_comment_searcher, $this->thread_color_updater)
        );
        return $creator->insert($new_comment);
    }

    public function testNewInlineCommentCanBeCreated(): void
    {
        $this->event_dispatcher = EventDispatcherStub::withCallback(
            static function (EventSubjectToNotification $event) {
                self::assertInstanceOf(PullRequestNewInlineCommentEvent::class, $event);
                return $event;
            }
        );

        $inline_comment = $this->create();
        self::assertSame(self::INSERTED_ID, $inline_comment->id);
        self::assertSame(ThreadColors::TLP_COLORS[0], $inline_comment->color);
        self::assertSame(1, $this->reference_manager->getCallCount());
        self::assertSame(1, $this->thread_color_updater->getCallCount());
    }
}
