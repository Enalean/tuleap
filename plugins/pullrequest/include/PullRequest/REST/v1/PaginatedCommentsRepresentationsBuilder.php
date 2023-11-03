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

use Tuleap\PullRequest\Comment\Factory;
use Tuleap\PullRequest\REST\v1\Comment\CommentRepresentationBuilder;
use Tuleap\User\REST\MinimalUserRepresentation;
use UserManager;

class PaginatedCommentsRepresentationsBuilder
{
    public function __construct(
        private readonly Factory $comment_factory,
        private readonly UserManager $user_manager,
        private readonly CommentRepresentationBuilder $comment_representation_builder,
    ) {
    }

    public function getPaginatedCommentsRepresentations(int $pull_request_id, $project_id, $limit, $offset, $order): PaginatedCommentsRepresentations
    {
        $paginated_comments       = $this->comment_factory->getPaginatedCommentsByPullRequestId($pull_request_id, $limit, $offset, $order);
        $comments_representations = [];

        foreach ($paginated_comments->getComments() as $comment) {
            $user = $this->user_manager->getUserById($comment->getUserId());
            if ($user === null) {
                continue;
            }
            $user_representation        = MinimalUserRepresentation::build($user);
            $comment_representation     = $this->comment_representation_builder
                    ->buildRepresentation(
                        $project_id,
                        $user_representation,
                        $comment
                    );
            $comments_representations[] = $comment_representation;
        }

        return new PaginatedCommentsRepresentations(
            $comments_representations,
            $paginated_comments->getTotalSize()
        );
    }
}
