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

use Codendi_HTMLPurifier;
use Tuleap\Markdown\CommonMarkInterpreter;
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
    public function __construct(private readonly Factory $timeline_factory, private readonly UserManager $user_manager, private readonly Codendi_HTMLPurifier $purifier, private readonly CommonMarkInterpreter $common_mark_interpreter)
    {
    }

    public function getPaginatedTimelineRepresentation(PullRequest $pull_request, $project_id, $limit, $offset)
    {
        $paginated_events        = $this->timeline_factory->getPaginatedTimelineByPullRequestId($pull_request, $limit, $offset);
        $timeline_representation = [];

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
        switch ($event::class) {
            case Comment::class:
                assert($event instanceof Comment);
                return CommentRepresentation::build(
                    $this->purifier,
                    $this->common_mark_interpreter,
                    $event->getId(),
                    $project_id,
                    $this->buildMinimalUserRepresentation($event->getUserId()),
                    $event->getColor(),
                    $event
                );
            case InlineComment::class:
                assert($event instanceof InlineComment);
                return new TimelineInlineCommentRepresentation(
                    $event->getFilePath(),
                    $event->getUnidiffOffset(),
                    $this->buildMinimalUserRepresentation($event->getUserId()),
                    $event->getPostDate(),
                    $event->getContent(),
                    $event->isOutdated(),
                    $project_id,
                    $event->getParentId(),
                    $event->getId(),
                    $event->getPosition(),
                    $event->getColor()
                );
            case TimelineGlobalEvent::class:
                assert($event instanceof TimelineGlobalEvent);
                return new TimelineEventRepresentation(
                    $this->buildMinimalUserRepresentation($event->getUserId()),
                    $event->getPostDate(),
                    $event->getType(),
                    0
                );
            case ReviewerChange::class:
                assert($event instanceof ReviewerChange);
                return ReviewerChangeTimelineEventRepresentation::fromReviewerChange($event);
        }

        throw new \LogicException('Do not know how to build a timeline event representation from ' . $event::class);
    }

    private function buildMinimalUserRepresentation(int $user_id): MinimalUserRepresentation
    {
        $user = $this->user_manager->getUserById($user_id);
        if ($user === null) {
            return MinimalUserRepresentation::build($this->user_manager->getUserAnonymous());
        }
        return MinimalUserRepresentation::build($user);
    }
}
