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

use BackendLogger;
use EventManager;
use Luracast\Restler\RestException;
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
use Tuleap\PullRequest\Comment\ThreadCommentColorAssigner;
use Tuleap\PullRequest\Comment\ThreadCommentColorRetriever;
use Tuleap\PullRequest\Comment\ThreadCommentDao;
use Tuleap\PullRequest\InlineComment\InlineCommentCreator;
use Tuleap\PullRequest\InlineComment\InlineCommentRetriever;
use Tuleap\PullRequest\Notification\PullRequestNotificationSupport;
use Tuleap\PullRequest\PullRequest\REST\v1\InlineComment\Reply\InlineCommentReplyPOSTRepresentation;
use Tuleap\PullRequest\PullRequest\REST\v1\InlineComment\Reply\POSTHandler;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\PullRequest\REST\v1\InlineComment\InlineCommentPATCHRepresentation;
use Tuleap\PullRequest\REST\v1\InlineComment\InlineCommentRepresentation;
use Tuleap\PullRequest\REST\v1\InlineComment\PATCHHandler;
use Tuleap\PullRequest\REST\v1\InlineComment\SingleRepresentationBuilder;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\User\Avatar\AvatarHashDao;
use Tuleap\User\Avatar\ComputeAvatarHash;
use Tuleap\User\Avatar\UserAvatarUrlProvider;

final class PullRequestInlineCommentsResource extends AuthenticatedResource
{
    public const ROUTE = 'pull_request_inline_comments';

    /**
     * @url OPTIONS {id}
     * @param int $id Inline comment ID
     */
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
            new SingleRepresentationBuilder($purifier, $markdown_interpreter),
            PullRequestNotificationSupport::buildDispatcher(new BackendLogger()),
            new UserAvatarUrlProvider(new AvatarHashDao(), new ComputeAvatarHash()),
        );
        return $handler->handle($current_user, $id, $comment_data, new \DateTimeImmutable())
            ->match(
                static fn(InlineCommentRepresentation $representation) => $representation,
                FaultMapper::mapToRestException(...)
            );
    }

    /**
     * @url OPTIONS {id}/reply
     * @param int $id Inline comment ID
     */
    public function optionsReply(int $id): void
    {
        Header::allowOptionsPost();
    }

    /**
     * Reply to a given inline comment
     *
     * <p>
     *     This route takes an inline comment id parameter:
     *     <ul>
     *         <li>To reply to a comment which is not part of a thread yet, provide its id.</li>
     *         <li>To reply to a comment in a thread, provide the id of the very first comment of the thread.</li>
     *     </ul>
     * </p>
     *
     * @url    POST {id}/reply
     * @access protected
     *
     * @param int                              $id           Root inline comment id
     * @param InlineCommentReplyPOSTRepresentation $reply_data {@from body}
     *
     * @status 200
     * @throws RestException 403
     * @throws RestException 404
     */
    public function postReply(int $id, InlineCommentReplyPOSTRepresentation $reply_data): InlineCommentRepresentation
    {
        Header::allowOptionsPost();
        $this->checkAccess();

        $dao             = new \Tuleap\PullRequest\InlineComment\Dao();
        $color_retriever = new ThreadCommentColorRetriever(new ThreadCommentDao(), $dao);
        $color_assigner  = new ThreadCommentColorAssigner($dao, $dao);
        $comment_creator = new InlineCommentCreator(
            $dao,
            ReferenceManager::instance(),
            PullRequestNotificationSupport::buildDispatcher(
                BackendLogger::getDefaultLogger()
            ),
            $color_retriever,
            $color_assigner
        );

        $git_repository_factory = new \GitRepositoryFactory(
            new \GitDao(),
            \ProjectManager::instance(),
        );

        $purifier             = \Codendi_HTMLPurifier::instance();
        $markdown_interpreter = CommonMarkInterpreter::build(
            $purifier,
            new EnhancedCodeBlockExtension(new CodeBlockFeatures())
        );

        return (
            new POSTHandler(
                new InlineCommentRetriever(new \Tuleap\PullRequest\InlineComment\Dao()),
                new PullRequestRetriever(new \Tuleap\PullRequest\Dao()),
                new PullRequestPermissionChecker(
                    $git_repository_factory,
                    new ProjectAccessChecker(
                        new RestrictedUserCanAccessProjectVerifier(),
                        EventManager::instance()
                    ),
                    new AccessControlVerifier(
                        new FineGrainedRetriever(new FineGrainedDao()),
                        new \System_Command()
                    ),
                ),
                $git_repository_factory,
                new SingleRepresentationBuilder($purifier, $markdown_interpreter),
                $comment_creator,
                new UserAvatarUrlProvider(new AvatarHashDao(), new ComputeAvatarHash()),
            )
        )->handle(
            $id,
            $reply_data,
            \UserManager::instance()->getCurrentUser(),
            new \DateTimeImmutable(),
        )->match(
            static fn(InlineCommentRepresentation $representation) => $representation,
            FaultMapper::mapToRestException(...)
        );
    }
}
