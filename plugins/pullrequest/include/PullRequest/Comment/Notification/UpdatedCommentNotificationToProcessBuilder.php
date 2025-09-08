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

namespace Tuleap\PullRequest\Comment\Notification;

use Tuleap\Notification\Mention\MentionedUserInTextRetriever;
use Tuleap\PullRequest\Comment\Comment;
use Tuleap\PullRequest\Comment\CommentRetriever;
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
 * @template-implements NotificationToProcessBuilder<UpdatedCommentEvent>
 */
final readonly class UpdatedCommentNotificationToProcessBuilder implements NotificationToProcessBuilder
{
    public function __construct(
        private RetrieveUserById $user_retriever,
        private PullRequestRetriever $pull_request_factory,
        private CommentRetriever $comment_retriever,
        private OwnerRetriever $owner_retriever,
        private FilterUserFromCollection $filter_user_from_collection,
        private UserHelper $user_helper,
        private HTMLURLBuilder $html_url_builder,
        private FormatNotificationContent $format_notification_content,
        private MentionedUserInTextRetriever $mentioned_user_retriever,
    ) {
    }

    #[\Override]
    public function getNotificationsToProcess(EventSubjectToNotification $event): array
    {
        return $this->comment_retriever->getCommentByID($event->comment_id)->mapOr(
            function (Comment $comment) {
                return $this->pull_request_factory->getPullRequestById($comment->getPullRequestId())->match(
                    function (PullRequest $pull_request) use ($comment) {
                        $change_user = $this->user_retriever->getUserById($comment->getUserId());
                        if ($change_user === null) {
                            return [];
                        }

                        $pull_request_owners       = $this->owner_retriever->getOwners($pull_request);
                        $mentioned_user_collection = $this->mentioned_user_retriever->getMentionedUsers($comment->getContent());

                        return [
                            UpdatedCommentNotification::fromOwnersAndUpdatedComment(
                                $this->user_helper,
                                $this->html_url_builder,
                                $this->filter_user_from_collection,
                                $this->format_notification_content,
                                $pull_request,
                                $change_user,
                                $pull_request_owners,
                                $comment,
                                $mentioned_user_collection,
                            ),
                        ];
                    },
                    static fn() => []
                );
            },
            []
        );
    }
}
