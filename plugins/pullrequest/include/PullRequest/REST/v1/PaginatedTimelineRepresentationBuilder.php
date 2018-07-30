<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use Tuleap\PullRequest\Timeline\Factory;
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

    public function getPaginatedTimelineRepresentation($pull_request_id, $project_id, $limit, $offset)
    {
        $paginated_events        = $this->timeline_factory->getPaginatedTimelineByPullRequestId($pull_request_id, $limit, $offset);
        $timeline_representation = array();

        foreach ($paginated_events->getEvents() as $event) {
            $timeline_representation[] = $this->buildEventRepresentation($event, $project_id);
        }

        return new PaginatedTimelineRepresentation(
            $timeline_representation,
            $paginated_events->getTotalSize()
        );
    }

    private function buildEventRepresentation($event, $project_id)
    {
        $user_representation = new MinimalUserRepresentation();
        $user_representation->build($this->user_manager->getUserById($event->getUserId()));

        $class_name  = get_class($event);
        $event_class = substr($class_name, strrpos($class_name, '\\') + 1);
        switch ($event_class) {
            case 'Comment':
                $event_representation = new CommentRepresentation();
                $event_representation->build(
                    $event->getId(),
                    $project_id,
                    $user_representation,
                    $event->getPostDate(),
                    $event->getContent()
                );
                return $event_representation;
            case 'InlineComment':
                $event_representation = new TimelineInlineCommentRepresentation();
                $event_representation->build(
                    $event->getFilePath(),
                    $event->getUnidiffOffset(),
                    $user_representation,
                    $event->getPostDate(),
                    $event->getContent(),
                    $event->isOutdated(),
                    $project_id
                );
                return $event_representation;
            case 'TimelineEvent':
                $event_representation = new TimelineEventRepresentation(
                    $user_representation,
                    $event->getPostDate(),
                    $event->getType()
                );
                return $event_representation;
        }
    }
}
