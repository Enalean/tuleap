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

namespace Tuleap\PullRequest\InlineComment\Notification;

use Tuleap\PullRequest\InlineComment\InlineComment;
use Tuleap\PullRequest\InlineComment\InlineCommentRetriever;
use Tuleap\PullRequest\Notification\EventSubjectToNotification;
use Tuleap\PullRequest\Notification\FilterUserFromCollection;
use Tuleap\PullRequest\Notification\FormatNotificationContent;
use Tuleap\PullRequest\Notification\NotificationToProcessBuilder;
use Tuleap\PullRequest\Notification\OwnerRetriever;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use Tuleap\User\RetrieveUserById;
use UserHelper;

/**
 * @template-implements NotificationToProcessBuilder<UpdatedInlineCommentEvent>
 */
final class UpdatedInlineCommentNotificationToProcessBuilder implements NotificationToProcessBuilder
{
    public function __construct(
        private readonly RetrieveUserById $user_retriever,
        private readonly PullRequestRetriever $pull_request_factory,
        private readonly InlineCommentRetriever $inline_comment_retriever,
        private readonly OwnerRetriever $owner_retriever,
        private readonly InlineCommentCodeContextExtractor $code_context_extractor,
        private readonly FilterUserFromCollection $filter_user_from_collection,
        private readonly UserHelper $user_helper,
        private readonly HTMLURLBuilder $html_url_builder,
        private readonly FormatNotificationContent $format_notification_content,
    ) {
    }

    public function getNotificationsToProcess(EventSubjectToNotification $event): array
    {
        return $this->inline_comment_retriever->getInlineCommentByID($event->inline_comment_id)->mapOr(
            function (InlineComment $comment) {
                return $this->pull_request_factory->getPullRequestById($comment->getPullRequestId())->match(
                    function (PullRequest $pull_request) use ($comment) {
                        $comment_author = $this->user_retriever->getUserById($comment->getUserId());
                        if ($comment_author === null) {
                            return [];
                        }

                        $pull_request_owners = $this->owner_retriever->getOwners($pull_request);

                        try {
                            return [
                                UpdatedInlineCommentNotification::fromOwnersAndUpdatedInlineComment(
                                    $this->user_helper,
                                    $this->html_url_builder,
                                    $this->filter_user_from_collection,
                                    $pull_request,
                                    $comment_author,
                                    $pull_request_owners,
                                    $comment,
                                    $this->code_context_extractor,
                                    $this->format_notification_content
                                ),
                            ];
                        } catch (InlineCommentCodeContextException $exception) {
                            return [];
                        }
                    },
                    static fn() => []
                );
            },
            []
        );
    }
}
