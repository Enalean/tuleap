<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\InlineComment;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\PullRequest\InlineComment\Notification\PullRequestNewInlineCommentEvent;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\REST\v1\PullRequestInlineCommentPOSTRepresentation;
use PFUser;
use ReferenceManager;
use pullrequestPlugin;

class InlineCommentCreator
{
    /**
     * @var Dao
     */
    private $dao;

    /**
     * @var ReferenceManager
     */
    private $reference_manager;
    /**
     * @var EventDispatcherInterface
     */
    private $event_dispatcher;

    public function __construct(
        Dao $dao,
        ReferenceManager $reference_manager,
        EventDispatcherInterface $event_dispatcher
    ) {
        $this->dao               = $dao;
        $this->reference_manager = $reference_manager;
        $this->event_dispatcher  = $event_dispatcher;
    }

    public function insert(
        PullRequest $pull_request,
        PFUser $user,
        PullRequestInlineCommentPOSTRepresentation $comment_data,
        $post_date,
        $project_id
    ): int {
        $pull_request_id = $pull_request->getId();

        $inserted = $this->dao->insert(
            $pull_request_id,
            $user->getId(),
            $comment_data->file_path,
            $post_date,
            $comment_data->unidiff_offset,
            $comment_data->content,
            $comment_data->position
        );

        $this->reference_manager->extractCrossRef(
            $comment_data->content,
            $pull_request_id,
            pullrequestPlugin::REFERENCE_NATURE,
            $project_id,
            $user->getId(),
            pullrequestPlugin::PULLREQUEST_REFERENCE_KEYWORD
        );

        $this->event_dispatcher->dispatch(PullRequestNewInlineCommentEvent::fromInlineCommentID($inserted));

        return $inserted;
    }
}
