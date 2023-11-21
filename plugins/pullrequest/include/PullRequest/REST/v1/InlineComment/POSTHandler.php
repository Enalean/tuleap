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

namespace Tuleap\PullRequest\REST\v1\InlineComment;

use Tuleap\Git\RetrieveGitRepository;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\PullRequest\Authorization\GitRepositoryNotFoundFault;
use Tuleap\PullRequest\InlineComment\InlineCommentCreator;
use Tuleap\PullRequest\InlineComment\NewInlineComment;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\PullRequest\REST\v1\PullRequestInlineCommentPOSTRepresentation;
use Tuleap\User\REST\MinimalUserRepresentation;

final class POSTHandler
{
    public function __construct(
        private readonly RetrieveGitRepository $repository_retriever,
        private readonly InlineCommentCreator $inline_comment_creator,
        private readonly SingleRepresentationBuilder $builder,
    ) {
    }

    /**
     * @return Ok<InlineCommentRepresentation> | Err<Fault>
     */
    public function handle(
        PullRequestInlineCommentPOSTRepresentation $comment_data,
        \PFUser $user,
        \DateTimeImmutable $post_date,
        PullRequest $pull_request,
    ): Ok|Err {
        $source_repository_id = $pull_request->getRepositoryId();
        $source_repository    = $this->repository_retriever->getRepositoryById($source_repository_id);
        if (! $source_repository) {
            return Result::err(GitRepositoryNotFoundFault::fromRepositoryId($source_repository_id));
        }

        $format = $comment_data->format;
        if (! $format) {
            $format = TimelineComment::FORMAT_MARKDOWN;
        }

        $new_comment             = new NewInlineComment(
            $pull_request,
            (int) $source_repository->getProjectId(),
            $comment_data->file_path,
            $comment_data->unidiff_offset,
            $comment_data->content,
            $format,
            $comment_data->position,
            $comment_data->parent_id ?? 0,
            $user,
            $post_date
        );
        $inserted_inline_comment = $this->inline_comment_creator->insert($new_comment);

        $representation = $this->builder->build(
            $new_comment->project_id,
            MinimalUserRepresentation::build($user),
            $inserted_inline_comment
        );
        return Result::ok($representation);
    }
}
