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
use Tuleap\Markdown\CodeBlockFeatures;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\Markdown\EnhancedCodeBlockExtension;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\FeatureFlagEditComments;
use Tuleap\PullRequest\InlineComment\InlineCommentRetriever;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\PullRequest\REST\v1\InlineComment\InlineCommentPATCHRepresentation;
use Tuleap\PullRequest\REST\v1\InlineComment\InlineCommentRepresentation;
use Tuleap\PullRequest\REST\v1\InlineComment\PATCHHandler;
use Tuleap\PullRequest\REST\v1\InlineComment\SingleRepresentationBuilder;
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
    public function patchCommentId(int $id, InlineCommentPATCHRepresentation $comment_data): InlineCommentRepresentation
    {
        Header::allowOptionsPatch();
        $this->checkAccess();

        if (! FeatureFlagEditComments::isCommentEditionEnabled()) {
            throw new RestException(501, 'This route is under construction');
        }

        $current_user           = \UserManager::instance()->getCurrentUser();
        $pull_request_dao       = new \Tuleap\PullRequest\Dao();
        $inline_comment_dao     = new \Tuleap\PullRequest\InlineComment\Dao();
        $git_repository_factory = new \GitRepositoryFactory(new \GitDao(), \ProjectManager::instance());
        $purifier               = \Codendi_HTMLPurifier::instance();
        $markdown_interpreter   = CommonMarkInterpreter::build(
            $purifier,
            new EnhancedCodeBlockExtension(new CodeBlockFeatures())
        );
        $handler                = new PATCHHandler(
            new InlineCommentRetriever($inline_comment_dao),
            new PullRequestRetriever($pull_request_dao),
            new PullRequestPermissionChecker(
                $git_repository_factory,
                new ProjectAccessChecker(
                    new RestrictedUserCanAccessProjectVerifier(),
                    \EventManager::instance()
                ),
                new AccessControlVerifier(
                    new FineGrainedRetriever(new FineGrainedDao()),
                    new \System_Command()
                )
            ),
            $inline_comment_dao,
            $git_repository_factory,
            new \ReferenceManager(),
            new SingleRepresentationBuilder($purifier, $markdown_interpreter)
        );
        return $handler->handle($current_user, $id, $comment_data, new \DateTimeImmutable())
            ->match(
                static fn(InlineCommentRepresentation $representation) => $representation,
                FaultMapper::mapToRestException(...)
            );
    }
}
