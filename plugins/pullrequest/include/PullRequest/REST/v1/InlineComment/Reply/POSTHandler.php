<?php
/**
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

namespace Tuleap\PullRequest\PullRequest\REST\v1\InlineComment\Reply;

use Tuleap\Git\RetrieveGitRepository;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\PullRequest\Authorization\CannotAccessToPullRequestFault;
use Tuleap\PullRequest\Authorization\CheckUserCanAccessPullRequest;
use Tuleap\PullRequest\Authorization\GitRepositoryNotFoundFault;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\InlineComment\InlineComment;
use Tuleap\PullRequest\InlineComment\InlineCommentCreator;
use Tuleap\PullRequest\InlineComment\InlineCommentNotFoundFault;
use Tuleap\PullRequest\InlineComment\InlineCommentRetriever;
use Tuleap\PullRequest\InlineComment\NewInlineComment;
use Tuleap\PullRequest\InlineComment\RootInlineCommentHasAParentFault;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\PullRequest\REST\v1\InlineComment\InlineCommentRepresentation;
use Tuleap\PullRequest\REST\v1\InlineComment\SingleRepresentationBuilder;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;
use Tuleap\User\REST\MinimalUserRepresentation;

final class POSTHandler
{
    public function __construct(
        private readonly InlineCommentRetriever $comment_retriever,
        private readonly PullRequestRetriever $pull_request_retriever,
        private readonly CheckUserCanAccessPullRequest $pull_request_permission_checker,
        private readonly RetrieveGitRepository $repository_retriever,
        private readonly SingleRepresentationBuilder $representation_builder,
        private readonly InlineCommentCreator $inline_comment_creator,
        private readonly ProvideUserAvatarUrl $provide_user_avatar_url,
    ) {
    }

    /**
     * @return Ok<InlineCommentRepresentation> | Err<Fault>
     */
    public function handle(
        int $parent_comment_id,
        InlineCommentReplyPOSTRepresentation $reply_data,
        \PFUser $user,
        \DateTimeImmutable $reply_post_date,
    ): Ok|Err {
        return $this->comment_retriever->getInlineCommentByID($parent_comment_id)
            ->okOr(Result::err(InlineCommentNotFoundFault::fromCommentId($parent_comment_id)))
            ->andThen(function (InlineComment $root_comment) use ($reply_data, $reply_post_date, $user) {
                if ($root_comment->getParentId() !== 0) {
                    return Result::err(RootInlineCommentHasAParentFault::fromParentCommentId($root_comment->getId()));
                }

                return $this->pull_request_retriever->getPullRequestById($root_comment->getPullRequestId())->andThen(
                    function (PullRequest $pull_request) use ($root_comment, $reply_data, $reply_post_date, $user) {
                        try {
                            $this->pull_request_permission_checker->checkPullRequestIsReadableByUser($pull_request, $user);
                        } catch (\GitRepoNotFoundException | UserCannotReadGitRepositoryException | \Project_AccessException $e) {
                            return Result::err(CannotAccessToPullRequestFault::fromUpdatingComment($e));
                        }
                        $source_repository_id = $pull_request->getRepositoryId();
                        $source_repository    = $this->repository_retriever->getRepositoryById($source_repository_id);
                        if (! $source_repository) {
                            return Result::err(GitRepositoryNotFoundFault::fromRepositoryId($source_repository_id));
                        }

                        $source_project_id = (int) $source_repository->getProjectId();

                        $reply = new NewInlineComment(
                            $pull_request,
                            $source_project_id,
                            $root_comment->getFilePath(),
                            $root_comment->getUnidiffOffset(),
                            $reply_data->content,
                            $reply_data->format,
                            $root_comment->getPosition(),
                            $root_comment->getId(),
                            $user,
                            $reply_post_date,
                        );

                        $inserted_reply = $this->inline_comment_creator->insert($reply);

                        $representation = $this->representation_builder->build(
                            $source_project_id,
                            MinimalUserRepresentation::build($user, $this->provide_user_avatar_url),
                            $inserted_reply
                        );
                        return Result::ok($representation);
                    }
                );
            });
    }
}
