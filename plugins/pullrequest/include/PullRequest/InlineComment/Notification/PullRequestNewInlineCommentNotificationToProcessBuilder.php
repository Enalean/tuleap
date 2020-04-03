<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\InlineComment\Notification;

use Tuleap\PullRequest\Exception\PullRequestNotFoundException;
use Tuleap\PullRequest\Factory;
use Tuleap\PullRequest\InlineComment\InlineCommentRetriever;
use Tuleap\PullRequest\Notification\EventSubjectToNotification;
use Tuleap\PullRequest\Notification\FilterUserFromCollection;
use Tuleap\PullRequest\Notification\NotificationToProcessBuilder;
use Tuleap\PullRequest\Notification\OwnerRetriever;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use UserHelper;
use UserManager;

/**
 * @template-implements NotificationToProcessBuilder<PullRequestNewInlineCommentEvent>
 */
final class PullRequestNewInlineCommentNotificationToProcessBuilder implements NotificationToProcessBuilder
{
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var Factory
     */
    private $pull_request_factory;
    /**
     * @var InlineCommentRetriever
     */
    private $inline_comment_retriever;
    /**
     * @var OwnerRetriever
     */
    private $owner_retriever;
    /**
     * @var InlineCommentCodeContextExtractor
     */
    private $code_context_extractor;
    /**
     * @var FilterUserFromCollection
     */
    private $filter_user_from_collection;
    /**
     * @var UserHelper
     */
    private $user_helper;
    /**
     * @var HTMLURLBuilder
     */
    private $html_url_builder;

    public function __construct(
        UserManager $user_manager,
        Factory $pull_request_factory,
        InlineCommentRetriever $inline_comment_retriever,
        OwnerRetriever $owner_retriever,
        InlineCommentCodeContextExtractor $code_context_extractor,
        FilterUserFromCollection $filter_user_from_collection,
        UserHelper $user_helper,
        HTMLURLBuilder $html_url_builder
    ) {
        $this->user_manager                = $user_manager;
        $this->pull_request_factory        = $pull_request_factory;
        $this->inline_comment_retriever    = $inline_comment_retriever;
        $this->owner_retriever             = $owner_retriever;
        $this->code_context_extractor      = $code_context_extractor;
        $this->filter_user_from_collection = $filter_user_from_collection;
        $this->user_helper                 = $user_helper;
        $this->html_url_builder            = $html_url_builder;
    }

    public function getNotificationsToProcess(EventSubjectToNotification $event): array
    {
        $comment = $this->inline_comment_retriever->getInlineCommentByID($event->getInlineCommentID());

        if ($comment === null) {
            return [];
        }

        try {
            $pull_request = $this->pull_request_factory->getPullRequestById($comment->getPullRequestId());
        } catch (PullRequestNotFoundException $e) {
            return [];
        }

        $change_user = $this->user_manager->getUserById($comment->getUserId());
        if ($change_user === null) {
            return [];
        }

        $pull_request_owners = $this->owner_retriever->getOwners($pull_request);

        try {
            return [
                PullRequestNewInlineCommentNotification::fromOwnersAndInlineComment(
                    $this->user_helper,
                    $this->html_url_builder,
                    $this->filter_user_from_collection,
                    $pull_request,
                    $change_user,
                    $pull_request_owners,
                    $comment,
                    $this->code_context_extractor
                )
            ];
        } catch (InlineCommentCodeContextException $exception) {
            return [];
        }
    }
}
