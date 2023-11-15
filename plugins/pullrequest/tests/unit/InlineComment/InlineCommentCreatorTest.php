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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Tuleap\PullRequest\Comment\ParentCommentSearcher;
use Tuleap\PullRequest\Comment\ThreadColorUpdater;
use Tuleap\PullRequest\Comment\ThreadCommentDao;
use Tuleap\PullRequest\InlineComment\Notification\PullRequestNewInlineCommentEvent;
use Tuleap\PullRequest\Notification\EventSubjectToNotification;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\PullRequest\REST\v1\Comment\ThreadCommentColorAssigner;
use Tuleap\PullRequest\REST\v1\Comment\ThreadCommentColorRetriever;
use Tuleap\PullRequest\REST\v1\PullRequestInlineCommentPOSTRepresentation;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\PullRequest\Tests\Stub\CreateInlineCommentStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Test\Stubs\ExtractAndSaveCrossReferencesStub;

final class InlineCommentCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const INSERTED_ID = 47;
    private PullRequestInlineCommentPOSTRepresentation $comment_data;
    private CreateInlineCommentStub $comment_saver;
    private ExtractAndSaveCrossReferencesStub $reference_manager;
    private ThreadCommentDao & Stub $thread_comment_dao;
    private ParentCommentSearcher & Stub $parent_comment_searcher;
    private ThreadColorUpdater & MockObject $thread_color_updater;
    private EventDispatcherStub $event_dispatcher;

    protected function setUp(): void
    {
        $this->comment_data = PullRequestInlineCommentPOSTRepresentation::build(
            'stuff',
            '/tmp',
            10,
            'right',
            1,
            TimelineComment::FORMAT_TEXT
        );

        $this->comment_saver           = CreateInlineCommentStub::withInsertedId(self::INSERTED_ID);
        $this->reference_manager       = ExtractAndSaveCrossReferencesStub::withCallCount();
        $this->event_dispatcher        = EventDispatcherStub::withIdentityCallback();
        $this->thread_comment_dao      = $this->createStub(ThreadCommentDao::class);
        $this->parent_comment_searcher = $this->createStub(ParentCommentSearcher::class);
        $this->thread_color_updater    = $this->createMock(ThreadColorUpdater::class);
    }

    private function create(): InsertedInlineComment
    {
        $pull_request   = PullRequestTestBuilder::aPullRequestInReview()->build();
        $comment_author = UserTestBuilder::buildWithDefaults();
        $post_date      = new \DateTimeImmutable('@1700000000');
        $project_id     = 127;

        $creator = new InlineCommentCreator(
            $this->comment_saver,
            $this->reference_manager,
            $this->event_dispatcher,
            new ThreadCommentColorRetriever($this->thread_comment_dao, $this->parent_comment_searcher),
            new ThreadCommentColorAssigner($this->parent_comment_searcher, $this->thread_color_updater)
        );
        return $creator->insert($pull_request, $comment_author, $this->comment_data, $post_date, $project_id);
    }

    public function testNewInlineCommentCanBeCreatedInTextFormat(): void
    {
        $this->thread_comment_dao->method('searchAllThreadByPullRequestId')->willReturn([]);
        $this->parent_comment_searcher->method('searchByCommentID')->willReturn(
            ['parent_id' => 0, 'id' => 1, 'color' => '']
        );
        $this->thread_color_updater->expects(self::once())->method('setThreadColor');

        $this->event_dispatcher = EventDispatcherStub::withCallback(static function (EventSubjectToNotification $event) {
            self::assertInstanceOf(PullRequestNewInlineCommentEvent::class, $event);
            return $event;
        });

        $inline_comment = $this->create();
        self::assertEquals(InsertedInlineComment::build(self::INSERTED_ID, 'graffiti-yellow'), $inline_comment);
        self::assertSame(1, $this->reference_manager->getCallCount());
    }

    public function testWhenFormatIsNotDefinedThenDefaultCommentFormatIsMarkdown(): void
    {
        $this->thread_comment_dao->method('searchAllThreadByPullRequestId')->willReturn([]);
        $this->parent_comment_searcher->method('searchByCommentID')->willReturn(
            ['parent_id' => 0, 'id' => 1, 'color' => '']
        );
        $this->thread_color_updater->expects(self::once())->method('setThreadColor');

        $this->comment_data = PullRequestInlineCommentPOSTRepresentation::build(
            'stuff',
            '/tmp',
            10,
            'right',
            1,
            ''
        );

        $inline_comment = $this->create();

        self::assertEquals(InsertedInlineComment::build(self::INSERTED_ID, 'graffiti-yellow'), $inline_comment);
        self::assertSame(TimelineComment::FORMAT_MARKDOWN, $this->comment_saver->getLastArgument()?->format);
    }
}
