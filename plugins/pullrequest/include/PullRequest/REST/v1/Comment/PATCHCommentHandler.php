<?php
/**
 * Copyright (c) Enalean, 2023-present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\PullRequest\REST\v1\Comment;

use GitRepoNotFoundException;
use PFUser;
use Project_AccessException;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\PullRequest\Authorization\CannotAccessToPullRequestFault;
use Tuleap\PullRequest\Authorization\CheckUserCanAccessPullRequest;
use Tuleap\PullRequest\Comment\Comment;
use Tuleap\PullRequest\Comment\CommentFormatNotAllowedFault;
use Tuleap\PullRequest\Comment\CommentIsNotFromCurrentUserFault;
use Tuleap\PullRequest\Comment\CommentNotFoundFault;
use Tuleap\PullRequest\Comment\CommentRetriever;
use Tuleap\PullRequest\Comment\CommentUpdater;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\PullRequest\REST\v1\CommentPATCHRepresentation;

final class PATCHCommentHandler
{
    public function __construct(
        private readonly CommentRetriever $comment_retriever,
        private readonly CommentUpdater $comment_dao,
        private readonly PullRequestRetriever $pull_request_retriever,
        private readonly CheckUserCanAccessPullRequest $pull_request_permission_checker,
    ) {
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    public function handle(PFUser $user, int $comment_id, CommentPATCHRepresentation $comment_data): Ok|Err
    {
        $comment_to_update_option = $this->comment_retriever->getCommentByID($comment_id);
        return $comment_to_update_option->okOr(Result::err(CommentNotFoundFault::withCommentId($comment_id)))
                ->andThen(
                    function (Comment $comment_to_update) use ($user, $comment_data) {
                        if ($comment_to_update->getFormat() !== TimelineComment::FORMAT_MARKDOWN) {
                            return Result::err(CommentFormatNotAllowedFault::withGivenFormat($comment_to_update->getFormat()));
                        }

                        $pull_request_result = $this->pull_request_retriever->getPullRequestById($comment_to_update->getPullRequestId());
                        return $pull_request_result->andThen(
                            function (PullRequest $pull_request) use ($comment_to_update, $user, $comment_data) {
                                try {
                                    $this->pull_request_permission_checker->checkPullRequestIsReadableByUser($pull_request, $user);
                                } catch (GitRepoNotFoundException | Project_AccessException | UserCannotReadGitRepositoryException $e) {
                                    return Result::err(CannotAccessToPullRequestFault::fromUpdatingComment($e));
                                }
                                if ((int) $user->getId() !== $comment_to_update->getUserId()) {
                                    return Result::err(CommentIsNotFromCurrentUserFault::fromComment());
                                }

                                $new_comment = Comment::buildWithNewContent($comment_to_update, $comment_data->content);
                                $this->comment_dao->updateComment($new_comment);
                                return Result::ok(null);
                            }
                        );
                    }
                );
    }
}
