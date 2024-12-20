<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1\Info;

use Luracast\Restler\RestException;
use PFUser;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\PullRequest\Factory as PullRequestFactory;
use Tuleap\PullRequest\Notification\PullRequestDescriptionUpdatedEvent;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\PullRequest\REST\v1\Permissions\PullRequestIsMergeableChecker;
use Tuleap\PullRequest\REST\v1\PullRequestPATCHRepresentation;

final readonly class PullRequestInfoUpdater
{
    public function __construct(
        private PullRequestFactory $pull_request_factory,
        private PullRequestIsMergeableChecker $pull_request_is_mergeable_checker,
        private EventDispatcherInterface $notification_event_dispatcher,
    ) {
    }

    /**
     * @throws RestException 400
     * @throws RestException 403
     */
    public function patchInfo(
        PFUser $user,
        PullRequest $pull_request,
        int $project_id,
        PullRequestPATCHRepresentation $body,
    ): void {
        if ($user->getId() !== $pull_request->getUserId()) {
            $this->pull_request_is_mergeable_checker->checkUserCanMerge($pull_request, $user);
        }

        if ($body->title !== null && trim($body->title) === '') {
            throw new RestException(400, 'Title cannot be empty');
        }

        if ($body->title !== null) {
            $this->pull_request_factory->updateTitle(
                $user,
                $pull_request,
                $project_id,
                $body->title
            );
        }

        if ($body->description !== null) {
            $format = $body->description_format;
            if ($format === null) {
                $format = TimelineComment::FORMAT_MARKDOWN;
            }
            $this->pull_request_factory->updateDescription(
                $user,
                $pull_request,
                $project_id,
                $body->description,
                $format
            );
        }

        $this->notification_event_dispatcher->dispatch(PullRequestDescriptionUpdatedEvent::fromPullRequestIdAndUserId($pull_request->getId(), (int) $user->getId()));
    }
}
