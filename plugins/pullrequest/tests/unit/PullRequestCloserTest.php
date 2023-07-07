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

namespace Tuleap\PullRequest;

use GitRepository;
use PFUser;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\PullRequest\Exception\PullRequestCannotBeAbandoned;
use Tuleap\PullRequest\Exception\PullRequestCannotBeMerged;
use Tuleap\PullRequest\StateStatus\PullRequestAbandonedEvent;
use Tuleap\PullRequest\StateStatus\PullRequestMergedEvent;
use Tuleap\PullRequest\Timeline\TimelineEventCreator;

final class PullRequestCloserTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Dao
     */
    private $dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PullRequestMerger
     */
    private $pull_request_merger;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TimelineEventCreator
     */
    private $timeline_event_creator;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&EventDispatcherInterface
     */
    private $event_dispatcher;

    private PullRequestCloser $pull_request_closer;

    protected function setUp(): void
    {
        $this->dao                    = $this->createMock(Dao::class);
        $this->pull_request_merger    = $this->createMock(PullRequestMerger::class);
        $this->timeline_event_creator = $this->createMock(TimelineEventCreator::class);
        $this->event_dispatcher       = $this->createMock(EventDispatcherInterface::class);

        $this->pull_request_closer = new PullRequestCloser(
            $this->dao,
            $this->pull_request_merger,
            $this->timeline_event_creator,
            $this->event_dispatcher
        );
    }

    public function testAPullRequestUnderReviewCanBeAbandoned(): void
    {
        $pull_request = $this->buildPullRequest(PullRequest::STATUS_REVIEW);

        $this->dao->expects(self::once())->method('markAsAbandoned');
        $this->timeline_event_creator->expects(self::once())->method('storeAbandonEvent');
        $this->event_dispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(PullRequestAbandonedEvent::class));

        $user = $this->createMock(PFUser::class);
        $user->method('getId')->willReturn(102);

        $this->pull_request_closer->abandon($pull_request, $user);
    }

    public function testAnAbandonedPullRequestCanBeRequestedToBeAbandonedAgainWithoutSideEffect(): void
    {
        $pull_request = $this->buildPullRequest(PullRequest::STATUS_ABANDONED);

        $this->dao->expects(self::never())->method('markAsAbandoned');
        $this->timeline_event_creator->expects(self::never())->method('storeAbandonEvent');

        $this->pull_request_closer->abandon($pull_request, $this->createMock(PFUser::class));
    }

    public function testAMergedPullRequestCannotBeAbandoned(): void
    {
        $pull_request = $this->buildPullRequest(PullRequest::STATUS_MERGED);

        $this->expectException(PullRequestCannotBeAbandoned::class);
        $this->pull_request_closer->abandon($pull_request, $this->createMock(PFUser::class));
    }

    public function testAPullRequestUnderReviewCanBeMerged(): void
    {
        $pull_request = $this->buildPullRequest(PullRequest::STATUS_REVIEW);

        $this->pull_request_merger->expects(self::once())->method('doMergeIntoDestination');
        $this->dao->expects(self::once())->method('markAsMerged');
        $this->timeline_event_creator->expects(self::once())->method('storeMergeEvent');
        $this->event_dispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(PullRequestMergedEvent::class));

        $user = $this->createMock(PFUser::class);
        $user->method('getId')->willReturn(102);

        $this->pull_request_closer->doMerge(
            $this->createMock(GitRepository::class),
            $pull_request,
            $user
        );
    }

    public function testAnMergedPullRequestCanBeRequestedToBeMergedAgainWithoutSideEffect(): void
    {
        $pull_request = $this->buildPullRequest(PullRequest::STATUS_MERGED);

        $this->pull_request_merger->expects(self::never())->method('doMergeIntoDestination');
        $this->dao->expects(self::never())->method('markAsMerged');
        $this->timeline_event_creator->expects(self::never())->method('storeMergeEvent');

        $this->pull_request_closer->doMerge(
            $this->createMock(GitRepository::class),
            $pull_request,
            $this->createMock(PFUser::class)
        );
    }

    public function testAnAbandonedPullRequestCannotBeMerged(): void
    {
        $pull_request = $this->buildPullRequest(PullRequest::STATUS_ABANDONED);

        $this->expectException(PullRequestCannotBeMerged::class);
        $this->pull_request_closer->doMerge(
            $this->createMock(GitRepository::class),
            $pull_request,
            $this->createMock(PFUser::class)
        );
    }

    public function testManuallyMergedPullRequestCanBeClosed(): void
    {
        $pull_request = $this->buildPullRequest(PullRequest::STATUS_REVIEW);

        $this->dao->expects(self::once())->method('markAsMerged');
        $this->timeline_event_creator->expects(self::once())->method('storeMergeEvent');
        $this->event_dispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(PullRequestMergedEvent::class));

        $user = $this->createMock(PFUser::class);
        $user->method('getId')->willReturn(102);

        $this->pull_request_closer->closeManuallyMergedPullRequest(
            $pull_request,
            $user
        );
    }

    private function buildPullRequest(string $status): PullRequest
    {
        return new PullRequest(
            1,
            'Title',
            'Description',
            78,
            101,
            1,
            'refs/heads/pr',
            '230549fc4be136fcae6ea6ed574c2f5c7b922346',
            87,
            'refs/heads/master',
            'fbe4dade4f744aa203ec35bf09f71475ecc3f9d6',
            $status
        );
    }
}
