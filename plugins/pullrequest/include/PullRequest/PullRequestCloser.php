<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\PullRequest\Exception\PullRequestCannotBeAbandoned;
use Tuleap\PullRequest\Exception\PullRequestCannotBeMerged;
use PFUser;
use GitRepository;
use Tuleap\PullRequest\StateStatus\PullRequestAbandonedEvent;
use Tuleap\PullRequest\StateStatus\PullRequestMergedEvent;
use Tuleap\PullRequest\Timeline\TimelineEventCreator;
use User;

class PullRequestCloser
{
    /**
     * @var Dao
     */
    private $pull_request_dao;
    /**
     * @var PullRequestMerger
     */
    private $pull_request_merger;
    /**
     * @var TimelineEventCreator
     */
    private $timeline_event_creator;
    /**
     * @var EventDispatcherInterface
     */
    private $event_dispatcher;

    public function __construct(
        Dao $pull_request_dao,
        PullRequestMerger $pull_request_merger,
        TimelineEventCreator $timeline_event_creator,
        EventDispatcherInterface $event_dispatcher
    ) {
        $this->pull_request_dao       = $pull_request_dao;
        $this->pull_request_merger    = $pull_request_merger;
        $this->timeline_event_creator = $timeline_event_creator;
        $this->event_dispatcher       = $event_dispatcher;
    }

    /**
     * @throws PullRequestCannotBeAbandoned
     */
    public function abandon(PullRequest $pull_request, PFUser $user_abandoning_the_pr): void
    {
        $status = $pull_request->getStatus();

        if ($status === PullRequest::STATUS_ABANDONED) {
            return;
        }

        if ($status === PullRequest::STATUS_MERGED) {
            throw new PullRequestCannotBeAbandoned('This pull request has already been merged, it can no longer be abandoned');
        }
        $this->pull_request_dao->markAsAbandoned($pull_request->getId());
        $this->timeline_event_creator->storeAbandonEvent($pull_request, $user_abandoning_the_pr);
        $this->event_dispatcher->dispatch(PullRequestAbandonedEvent::fromPullRequestAndUserAbandoningThePullRequest(
            $pull_request,
            $user_abandoning_the_pr
        ));
    }

    /**
     * @throws PullRequestCannotBeMerged
     */
    public function doMerge(
        GitRepository $repository_dest,
        PullRequest $pull_request,
        PFUser $user_requesting_merge
    ): void {
        $this->closePullRequestViaAMergeAction(
            $pull_request,
            $user_requesting_merge,
            function () use ($user_requesting_merge, $repository_dest, $pull_request): void {
                $this->pull_request_merger->doMergeIntoDestination($pull_request, $repository_dest, $user_requesting_merge);
            }
        );
    }

    public function closeManuallyMergedPullRequest(
        PullRequest $pull_request,
        PFUser $merger
    ): void {
        $this->closePullRequestViaAMergeAction($pull_request, $merger, static function () {
        });
    }

    /**
     * @throws PullRequestCannotBeMerged
     *
     * @psalm-param callable():void $actions_to_execute_before_marking_as_merged
     */
    private function closePullRequestViaAMergeAction(
        PullRequest $pull_request,
        PFUser $user,
        callable $actions_to_execute_before_marking_as_merged
    ): void {
        $status = $pull_request->getStatus();

        if ($status === PullRequest::STATUS_MERGED) {
            return;
        }

        if ($status === PullRequest::STATUS_ABANDONED) {
            throw new PullRequestCannotBeMerged(
                'This pull request has already been abandoned, it can no longer be merged'
            );
        }

        $actions_to_execute_before_marking_as_merged();

        $this->pull_request_dao->markAsMerged($pull_request->getId());
        $this->timeline_event_creator->storeMergeEvent($pull_request, $user);
        $this->event_dispatcher->dispatch(PullRequestMergedEvent::fromPullRequestAndUserMergingThePullRequest(
            $pull_request,
            $user
        ));
    }
}
