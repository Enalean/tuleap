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
use Tuleap\PullRequest\Authorization\CannotAccessToPullRequestFault;
use Tuleap\PullRequest\Authorization\CheckUserCanAccessPullRequest;
use Tuleap\PullRequest\Authorization\GitRepositoryNotFoundFault;
use Tuleap\PullRequest\Comment\CommentFormatNotAllowedFault;
use Tuleap\PullRequest\Comment\CommentIsNotFromCurrentUserFault;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\InlineComment\InlineComment;
use Tuleap\PullRequest\InlineComment\InlineCommentNotFoundFault;
use Tuleap\PullRequest\InlineComment\InlineCommentRetriever;
use Tuleap\PullRequest\InlineComment\InlineCommentSaver;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\Reference\ExtractAndSaveCrossReferences;
use Tuleap\User\REST\MinimalUserRepresentation;

final class PATCHHandler
{
    public function __construct(
        private readonly InlineCommentRetriever $comment_retriever,
        private readonly PullRequestRetriever $pull_request_retriever,
        private readonly CheckUserCanAccessPullRequest $pull_request_permission_checker,
        private readonly InlineCommentSaver $comment_saver,
        private readonly RetrieveGitRepository $repository_retriever,
        private readonly ExtractAndSaveCrossReferences $cross_references_saver,
        private readonly SingleRepresentationBuilder $representation_builder,
    ) {
    }

    /**
     * @return Ok<InlineCommentRepresentation> | Err<Fault>
     */
    public function handle(
        \PFUser $user,
        int $inline_comment_id,
        InlineCommentPATCHRepresentation $comment_data,
        \DateTimeImmutable $comment_edition_date,
    ): Ok|Err {
        return $this->comment_retriever
            ->getInlineCommentByID($inline_comment_id)
            ->okOr(Result::err(InlineCommentNotFoundFault::fromCommentId($inline_comment_id)))
            ->andThen(function (InlineComment $comment) use ($comment_edition_date, $comment_data, $user) {
                if ($comment->getFormat() !== TimelineComment::FORMAT_MARKDOWN) {
                    return Result::err(CommentFormatNotAllowedFault::withGivenFormat($comment->getFormat()));
                }

                if ((int) $user->getId() !== $comment->getUserId()) {
                    return Result::err(CommentIsNotFromCurrentUserFault::fromComment());
                }

                return $this->pull_request_retriever->getPullRequestById($comment->getPullRequestId())
                    ->andThen(function (PullRequest $pull_request) use ($comment_edition_date, $comment_data, $comment, $user) {
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

                        $updated_comment = InlineComment::buildWithNewContent($comment, $comment_data->content, $comment_edition_date);
                        $this->comment_saver->saveUpdatedComment($updated_comment);
                        $this->cross_references_saver->extractCrossRef(
                            $updated_comment->getContent(),
                            $pull_request->getId(),
                            \pullrequestPlugin::REFERENCE_NATURE,
                            $source_project_id,
                            $user->getId(),
                            \pullrequestPlugin::PULLREQUEST_REFERENCE_KEYWORD
                        );
                        $representation = $this->representation_builder->build(
                            $source_project_id,
                            MinimalUserRepresentation::build($user),
                            $updated_comment
                        );
                        return Result::ok($representation);
                    });
            });
    }
}
