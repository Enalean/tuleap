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

namespace Tuleap\PullRequest\REST\v1;

use Tuleap\PullRequest\Comment\Comment;
use Tuleap\PullRequest\InlineComment\InlineComment;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\REST\v1\Reviewer\ReviewerChangeTimelineEventRepresentation;
use Tuleap\PullRequest\Reviewer\Change\ReviewerChange;
use Tuleap\PullRequest\Timeline\Factory;
use Tuleap\PullRequest\Timeline\TimelineEvent;
use Tuleap\PullRequest\Timeline\TimelineGlobalEvent;
use Tuleap\User\REST\MinimalUserRepresentation;
use UserManager;

class PaginatedTimelineRepresentationBuilder
{

    /** @var Tuleap\PullRequest\Timeline\Factory */
    private $timeline_factory;

    /** @var UserManager */
    private $user_manager;


    public function __construct(Factory $timeline_factory)
    {
        $this->timeline_factory = $timeline_factory;
        $this->user_manager     = UserManager::instance();
    }

    public function getPaginatedTimelineRepresentation(PullRequest $pull_request, $project_id, $limit, $offset)
    {
        $paginated_events        = $this->timeline_factory->getPaginatedTimelineByPullRequestId($pull_request, $limit, $offset);
        $timeline_representation = array();

        foreach ($paginated_events->getEvents() as $event) {
            $timeline_representation[] = $this->buildEventRepresentation($event, $project_id);
        }

        return new PaginatedTimelineRepresentation(
            $timeline_representation,
            $paginated_events->getTotalSize()
        );
    }

    private function buildEventRepresentation(TimelineEvent $event, $project_id)
    {
        switch (get_class($event)) {
            case Comment::class:
                $event_representation = new CommentRepresentation();
                $event_representation->build(
                    $event->getId(),
                    $project_id,
                    $this->buildMinimalUserRepresentation($event->getUserId()),
                    $event->getPostDate(),
                    $event->getContent()
                );
                return $event_representation;
            case InlineComment::class:
                $event_representation = new TimelineInlineCommentRepresentation();
                $event_representation->build(
                    $event->getFilePath(),
                    $event->getUnidiffOffset(),
                    $this->buildMinimalUserRepresentation($event->getUserId()),
                    $event->getPostDate(),
                    $event->getContent(),
                    $event->isOutdated(),
                    $project_id
                );
                return $event_representation;
            case TimelineGlobalEvent::class:
                $event_representation = new TimelineEventRepresentation(
                    $this->buildMinimalUserRepresentation($event->getUserId()),
                    $event->getPostDate(),
                    $event->getType()
                );
                return $event_representation;
            case ReviewerChange::class:
                return ReviewerChangeTimelineEventRepresentation::fromReviewerChange($event);
        }

        throw new \LogicException('Do not know how to build a timeline event representation from ' . get_class($event));
    }

    private function buildMinimalUserRepresentation(int $user_id): MinimalUserRepresentation
    {
        $user_representation = new MinimalUserRepresentation();
        $user                = $this->user_manager->getUserById($user_id);
        if ($user === null) {
            $user_representation->build($this->user_manager->getUserAnonymous());
        } else {
            $user_representation->build($user);
        }
        return $user_representation;
    }
}
