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

use Tuleap\PullRequest\Comment\Factory;
use Tuleap\User\REST\MinimalUserRepresentation;
use UserManager;

class PaginatedCommentsRepresentationsBuilder
{

    /** @var Tuleap\PullRequest\Comment\Factory */
    private $comment_factory;

    /** @var UserManager */
    private $user_manager;

    public function __construct(Factory $comment_factory)
    {
        $this->comment_factory = $comment_factory;
        $this->user_manager    = UserManager::instance();
    }

    public function getPaginatedCommentsRepresentations($pull_request_id, $project_id, $limit, $offset, $order)
    {
        $paginated_comments       = $this->comment_factory->getPaginatedCommentsByPullRequestId($pull_request_id, $limit, $offset, $order);
        $comments_representations = array();

        foreach ($paginated_comments->getComments() as $comment) {
            $user_representation = new MinimalUserRepresentation();
            $user = $this->user_manager->getUserById($comment->getUserId());
            if ($user === null) {
                continue;
            }
            $user_representation->build($user);

            $comment_representation = new CommentRepresentation();
            $comment_representation->build($comment->getId(), $project_id, $user_representation, $comment->getPostDate(), $comment->getContent());
            $comments_representations[] = $comment_representation;
        }

        return new PaginatedCommentsRepresentations(
            $comments_representations,
            $paginated_comments->getTotalSize()
        );
    }
}
