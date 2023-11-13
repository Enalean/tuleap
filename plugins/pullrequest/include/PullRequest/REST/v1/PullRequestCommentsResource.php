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

namespace Tuleap\PullRequest\REST\v1;

use DateTimeImmutable;
use EventManager;
use GitDao;
use GitRepositoryFactory;
use Luracast\Restler\RestException;
use ProjectManager;
use ReferenceManager;
use Tuleap\Git\Permissions\AccessControlVerifier;
use Tuleap\Git\Permissions\FineGrainedDao;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Markdown\CodeBlockFeatures;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\Markdown\EnhancedCodeBlockExtension;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Comment\CommentRetriever;
use Tuleap\PullRequest\Comment\Dao as CommentDao;
use Tuleap\PullRequest\Dao as PullRequestDao;
use Tuleap\PullRequest\FeatureFlagEditComments;
use Tuleap\PullRequest\PullRequest\REST\v1\AccessiblePullRequestRESTRetriever;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\PullRequest\REST\v1\Comment\CommentRepresentation;
use Tuleap\PullRequest\REST\v1\Comment\CommentRepresentationBuilder;
use Tuleap\PullRequest\REST\v1\Comment\PATCHCommentHandler;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use UserManager;

final class PullRequestCommentsResource extends AuthenticatedResource
{
    /**
     * @url OPTIONS {id}
     */
    public function optionsCommentId(int $id): void
    {
        Header::allowOptionsPatch();
    }

    public const ROUTE = 'pull_request_comments';

    /**
     * Update an existing comment
     *
     * Update a comment for a given pull request <br>
     * Format: {"content": "My updated comment" }
     *
     * @url PATCH {id}
     *
     * @access protected
     *
     * @param int $id Comment id
     * @param CommentPATCHRepresentation $comment_data Comment {@from body}
     *
     *
     * @status 200
     * @throws RestException 403
     * @throws RestException 404
     */
    protected function patchCommentId(int $id, CommentPATCHRepresentation $comment_data): CommentRepresentation
    {
        $this->checkAccess();
        Header::allowOptionsPatch();

        if (! FeatureFlagEditComments::isCommentEditionEnabled()) {
            throw new RestException(501, "This route is under construction");
        }

        $comment_dao      = new CommentDao();
        $pull_request_dao = new PullRequestDao();

        $git_repository_factory = new GitRepositoryFactory(
            new GitDao(),
            ProjectManager::instance()
        );

        $project_access_checker = new ProjectAccessChecker(
            new RestrictedUserCanAccessProjectVerifier(),
            EventManager::instance()
        );

        $access_controller_verifier = new AccessControlVerifier(
            new FineGrainedRetriever(new FineGrainedDao()),
            new \System_Command()
        );

        $purifier = \Codendi_HTMLPurifier::instance();

        $content_interpreter = CommonMarkInterpreter::build(
            $purifier,
            new EnhancedCodeBlockExtension(new CodeBlockFeatures())
        );

        $accessible_pull_request_retriever = new AccessiblePullRequestRESTRetriever(
            new PullRequestRetriever($pull_request_dao),
            new PullRequestPermissionChecker(
                $git_repository_factory,
                $project_access_checker,
                $access_controller_verifier
            )
        );
        $comment_patch_handler             = new PATCHCommentHandler(
            new CommentRetriever($comment_dao),
            $comment_dao,
            $accessible_pull_request_retriever,
            new CommentRepresentationBuilder($purifier, $content_interpreter),
            $git_repository_factory,
            ReferenceManager::instance()
        );

        $current_user = UserManager::instance()->getCurrentUser();

        return $comment_patch_handler->handle($current_user, $id, $comment_data, new DateTimeImmutable())->match(
            static fn(CommentRepresentation $updated_comment) => $updated_comment,
            FaultMapper::mapToRestException(...)
        );
    }
}
