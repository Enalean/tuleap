<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset\Comment;

final class CommentPresenter
{
    /**
     * @var bool
     */
    public $has_parent;
    /**
     * @var string
     */
    public $format;
    /**
     * @var string
     */
    public $purified_body;
    /**
     * @var int
     */
    public $changeset_id;
    /**
     * @var bool
     */
    public $is_empty;
    /**
     * @var bool
     */
    public $was_cleared;
    /**
     * @var string
     */
    public $user_link;
    /**
     * @var string
     */
    public $relative_date;

    public function __construct(
        \Tracker_Artifact_Changeset_Comment $comment,
        \UserHelper $user_helper,
        \PFUser $current_user
    ) {
        $this->has_parent = (bool) $comment->parent_id;
        // consider markdown format to be similar to text one for now
        $considered_body_format = $comment->bodyFormat;
        if ($considered_body_format === \Tracker_Artifact_Changeset_Comment::COMMONMARK_COMMENT) {
            $considered_body_format = \Tracker_Artifact_Changeset_Comment::TEXT_COMMENT;
        }
        $this->format        = $considered_body_format;
        $this->purified_body = $comment->getPurifiedBodyForHTML();
        $this->changeset_id  = (int) $comment->changeset->getId();
        $this->is_empty      = $comment->hasEmptyBody();
        $this->was_cleared   = ($this->has_parent && ! trim($comment->body));
        $this->user_link     = $user_helper->getLinkOnUserFromUserId((int) $comment->submitted_by);
        $this->relative_date = \DateHelper::relativeDateInlineContext((int) $comment->submitted_on, $current_user);
    }
}
