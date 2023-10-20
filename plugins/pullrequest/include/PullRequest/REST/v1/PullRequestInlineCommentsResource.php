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

namespace Tuleap\PullRequest\REST\v1;

use Luracast\Restler\RestException;
use Tuleap\Git\Permissions\AccessControlVerifier;
use Tuleap\Git\Permissions\FineGrainedDao;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\FeatureFlagEditComments;
use Tuleap\PullRequest\InlineComment\Dao;
use Tuleap\PullRequest\InlineComment\InlineCommentRetriever;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\PullRequest\REST\v1\Comment\InlineCommentPATCHRepresentation;
use Tuleap\PullRequest\REST\v1\Comment\PATCHInlineCommentHandler;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;

final class PullRequestInlineCommentsResource extends AuthenticatedResource
{
    public const ROUTE = 'pull_request_inline_comments';

    public function optionsCommentId(int $id): void
    {
        Header::allowOptionsPatch();
    }

    /**
     * Update an existing comment
     *
     * Only the comment's content is allowed to be modified.
     *
     * @url    PATCH {id}
     * @access protected
     *
     * @param int                              $id           Comment id
     * @param InlineCommentPATCHRepresentation $comment_data {@from body}
     *
     * @status 200
     * @throws RestException 403
     * @throws RestException 404
     */
    public function patchCommentId(int $id, InlineCommentPATCHRepresentation $comment_data): void
    {
        Header::allowOptionsPatch();
        $this->checkAccess();

        if (! FeatureFlagEditComments::isCommentEditionEnabled()) {
            throw new RestException(501, 'This route is under construction');
        }

        $current_user     = \UserManager::instance()->getCurrentUser();
        $pull_request_dao = new \Tuleap\PullRequest\Dao();
        $handler          = new PATCHInlineCommentHandler(
            new InlineCommentRetriever(new Dao()),
            new PullRequestRetriever($pull_request_dao),
            new PullRequestPermissionChecker(
                new \GitRepositoryFactory(new \GitDao(), \ProjectManager::instance()),
                new ProjectAccessChecker(
                    new RestrictedUserCanAccessProjectVerifier(),
                    \EventManager::instance()
                ),
                new AccessControlVerifier(
                    new FineGrainedRetriever(new FineGrainedDao()),
                    new \System_Command()
                )
            )
        );
        $handler->handle($current_user, $id, $comment_data)->mapErr(FaultMapper::mapToRestException(...));
    }
}
