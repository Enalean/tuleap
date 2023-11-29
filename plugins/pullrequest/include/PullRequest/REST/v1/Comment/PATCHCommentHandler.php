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

use DateTimeImmutable;
use PFUser;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Git\RetrieveGitRepository;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\PullRequest\Authorization\GitRepositoryNotFoundFault;
use Tuleap\PullRequest\Comment\Comment;
use Tuleap\PullRequest\Comment\CommentFormatNotAllowedFault;
use Tuleap\PullRequest\Comment\CommentIsNotFromCurrentUserFault;
use Tuleap\PullRequest\Comment\CommentNotFoundFault;
use Tuleap\PullRequest\Comment\CommentRetriever;
use Tuleap\PullRequest\Comment\CommentUpdater;
use Tuleap\PullRequest\Comment\Notification\UpdatedCommentEvent;
use Tuleap\PullRequest\PullRequest\REST\v1\AccessiblePullRequestRESTRetriever;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\PullRequest\REST\v1\CommentPATCHRepresentation;
use Tuleap\Reference\ExtractAndSaveCrossReferences;
use Tuleap\User\REST\MinimalUserRepresentation;

final class PATCHCommentHandler
{
    public function __construct(
        private readonly CommentRetriever $comment_retriever,
        private readonly CommentUpdater $comment_dao,
        private readonly AccessiblePullRequestRESTRetriever $pull_request_permission_retriever,
        private readonly CommentRepresentationBuilder $comment_representation_builder,
        private readonly RetrieveGitRepository $git_repository_factory,
        private readonly ExtractAndSaveCrossReferences $cross_references_saver,
        private readonly EventDispatcherInterface $event_manager,
    ) {
    }

    /**
     * @return Ok<CommentRepresentation>|Err<Fault>
     * @throw RestException
     */
    public function handle(PFUser $user, int $comment_id, CommentPATCHRepresentation $comment_data, DateTimeImmutable $comment_edition_time): Ok|Err
    {
        $comment_to_update_option = $this->comment_retriever->getCommentByID($comment_id);
        return $comment_to_update_option->okOr(Result::err(CommentNotFoundFault::withCommentId($comment_id)))
                ->andThen(
                    function (Comment $comment_to_update) use ($user, $comment_data, $comment_edition_time) {
                        if ($comment_to_update->getFormat() !== TimelineComment::FORMAT_MARKDOWN) {
                            return Result::err(CommentFormatNotAllowedFault::withGivenFormat($comment_to_update->getFormat()));
                        }

                        if ((int) $user->getId() !== $comment_to_update->getUserId()) {
                            return Result::err(CommentIsNotFromCurrentUserFault::fromComment());
                        }

                        $pull_request         = $this->pull_request_permission_retriever->getAccessiblePullRequest($comment_to_update->getPullRequestId(), $user);
                        $source_repository_id = $pull_request->getRepositoryId();
                        $source_repository    = $this->git_repository_factory->getRepositoryById($source_repository_id);
                        if (! $source_repository) {
                            return Result::err(GitRepositoryNotFoundFault::fromRepositoryId($source_repository_id));
                        }

                        $source_project_id = (int) $source_repository->getProject()->getID();

                        $new_comment = Comment::buildWithNewContent($comment_to_update, $comment_data->content, $comment_edition_time);
                        $this->comment_dao->updateComment($new_comment);

                        $this->event_manager->dispatch(UpdatedCommentEvent::fromUpdatedComment($new_comment));

                        $this->cross_references_saver->extractCrossRef(
                            $new_comment->getContent(),
                            $new_comment->getPullRequestId(),
                            \pullrequestPlugin::REFERENCE_NATURE,
                            $source_project_id,
                            $user->getId(),
                            \pullrequestPlugin::PULLREQUEST_REFERENCE_KEYWORD
                        );

                        return Result::ok(
                            $this->comment_representation_builder->buildRepresentation(
                                $source_project_id,
                                MinimalUserRepresentation::build($user),
                                $new_comment
                            )
                        );
                    }
                );
    }
}
