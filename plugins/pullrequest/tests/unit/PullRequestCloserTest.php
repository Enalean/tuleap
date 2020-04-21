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
use Mockery;
use PFUser;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\PullRequest\Exception\PullRequestCannotBeAbandoned;
use Tuleap\PullRequest\Exception\PullRequestCannotBeMerged;
use Tuleap\PullRequest\StateStatus\PullRequestAbandonedEvent;
use Tuleap\PullRequest\StateStatus\PullRequestMergedEvent;
use Tuleap\PullRequest\Timeline\TimelineEventCreator;

final class PullRequestCloserTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Dao
     */
    private $dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PullRequestMerger
     */
    private $pull_request_merger;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TimelineEventCreator
     */
    private $timeline_event_creator;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|EventDispatcherInterface
     */
    private $event_dispatcher;

    /**
     * @var PullRequestCloser
     */
    private $pull_request_closer;

    protected function setUp(): void
    {
        $this->dao                    = Mockery::mock(Dao::class);
        $this->pull_request_merger    = Mockery::mock(PullRequestMerger::class);
        $this->timeline_event_creator = Mockery::mock(TimelineEventCreator::class);
        $this->event_dispatcher       = Mockery::mock(EventDispatcherInterface::class);

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

        $this->dao->shouldReceive('markAsAbandoned')->once();
        $this->timeline_event_creator->shouldReceive('storeAbandonEvent')->once();
        $this->event_dispatcher->shouldReceive('dispatch')
            ->with(Mockery::type(PullRequestAbandonedEvent::class))->once();

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getId')->andReturn(102);

        $this->pull_request_closer->abandon($pull_request, $user);
    }

    public function testAnAbandonedPullRequestCanBeRequestedToBeAbandonedAgainWithoutSideEffect(): void
    {
        $pull_request = $this->buildPullRequest(PullRequest::STATUS_ABANDONED);

        $this->dao->shouldNotReceive('markAsAbandoned');
        $this->timeline_event_creator->shouldNotReceive('storeAbandonEvent');

        $this->pull_request_closer->abandon($pull_request, Mockery::mock(PFUser::class));
    }

    public function testAMergedPullRequestCannotBeAbandoned(): void
    {
        $pull_request = $this->buildPullRequest(PullRequest::STATUS_MERGED);

        $this->expectException(PullRequestCannotBeAbandoned::class);
        $this->pull_request_closer->abandon($pull_request, Mockery::mock(PFUser::class));
    }

    public function testAPullRequestUnderReviewCanBeMerged(): void
    {
        $pull_request = $this->buildPullRequest(PullRequest::STATUS_REVIEW);

        $this->pull_request_merger->shouldReceive('doMergeIntoDestination')->once();
        $this->dao->shouldReceive('markAsMerged')->once();
        $this->timeline_event_creator->shouldReceive('storeMergeEvent')->once();
        $this->event_dispatcher->shouldReceive('dispatch')
            ->with(Mockery::type(PullRequestMergedEvent::class))->once();

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getId')->andReturn(102);

        $this->pull_request_closer->doMerge(
            Mockery::mock(GitRepository::class),
            $pull_request,
            $user
        );
    }

    public function testAnMergedPullRequestCanBeRequestedToBeMergedAgainWithoutSideEffect(): void
    {
        $pull_request = $this->buildPullRequest(PullRequest::STATUS_MERGED);

        $this->pull_request_merger->shouldNotReceive('doMergeIntoDestination');
        $this->dao->shouldNotReceive('markAsMerged');
        $this->timeline_event_creator->shouldNotReceive('storeMergeEvent');

        $this->pull_request_closer->doMerge(
            Mockery::mock(GitRepository::class),
            $pull_request,
            Mockery::mock(PFUser::class)
        );
    }

    public function testAnAbandonedPullRequestCannotBeMerged(): void
    {
        $pull_request = $this->buildPullRequest(PullRequest::STATUS_ABANDONED);

        $this->expectException(PullRequestCannotBeMerged::class);
        $this->pull_request_closer->doMerge(
            Mockery::mock(GitRepository::class),
            $pull_request,
            Mockery::mock(PFUser::class)
        );
    }

    public function testManuallyMergedPullRequestCanBeClosed(): void
    {
        $pull_request = $this->buildPullRequest(PullRequest::STATUS_REVIEW);

        $this->dao->shouldReceive('markAsMerged')->once();
        $this->timeline_event_creator->shouldReceive('storeMergeEvent')->once();
        $this->event_dispatcher->shouldReceive('dispatch')
            ->with(Mockery::type(PullRequestMergedEvent::class))->once();

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getId')->andReturn(102);

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
