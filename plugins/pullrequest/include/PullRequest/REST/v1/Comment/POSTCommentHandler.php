<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1\Comment;

use PFUser;
use Tuleap\Git\RetrieveGitRepository;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Option\Option;
use Tuleap\PullRequest\Authorization\GitRepositoryNotFoundFault;
use Tuleap\PullRequest\Comment\Comment;
use Tuleap\PullRequest\Comment\CommentCreator;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\PullRequest\REST\v1\CommentPOSTRepresentation;
use Tuleap\User\REST\MinimalUserRepresentation;

final class POSTCommentHandler
{
    public function __construct(
        private readonly RetrieveGitRepository $repository_retriever,
        private readonly CommentCreator $comment_creator,
        private readonly CommentRepresentationBuilder $builder,
    ) {
    }

    /**
     * @return Ok<CommentRepresentation> | Err<Fault>
     */
    public function handle(
        CommentPOSTRepresentation $comment_data,
        PFUser $user,
        \DateTimeImmutable $post_date,
        PullRequest $pull_request,
    ): Ok|Err {
        $source_repository_id = $pull_request->getRepositoryId();
        $source_repository    = $this->repository_retriever->getRepositoryById($source_repository_id);
        if (! $source_repository) {
            return Result::err(GitRepositoryNotFoundFault::fromRepositoryId($source_repository_id));
        }
        $source_project_id = (int) $source_repository->getProjectId();

        $format = $comment_data->format;
        if (! $format) {
            $format = TimelineComment::FORMAT_MARKDOWN;
        }

        $comment     = new Comment(
            0,
            $pull_request->getId(),
            (int) $user->getId(),
            $post_date,
            $comment_data->content,
            $comment_data->parent_id ?? 0,
            '',
            $format,
            Option::nothing(\DateTimeImmutable::class)
        );
        $new_comment = $this->comment_creator->create($comment, $source_project_id);

        $representation = $this->builder->buildRepresentation(
            $source_project_id,
            MinimalUserRepresentation::build($user),
            $new_comment
        );
        return Result::ok($representation);
    }
}
