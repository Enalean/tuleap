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

namespace Tuleap\PullRequest\Comment\Notification;

use PFUser;
use TemplateRendererFactory;
use Tuleap\PullRequest\Comment\Comment;
use Tuleap\PullRequest\Notification\FilterUserFromCollection;
use Tuleap\PullRequest\Notification\FormatNotificationContent;
use Tuleap\PullRequest\Notification\NotificationEnhancedContent;
use Tuleap\PullRequest\Notification\NotificationTemplatedContent;
use Tuleap\PullRequest\Notification\NotificationToProcess;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use UserHelper;

/**
 * @psalm-mutation-free
 */
final class UpdatedCommentNotification implements NotificationToProcess
{
    public function __construct(
        private readonly PullRequest $pull_request,
        private readonly string $comment_author_name,
        private readonly array $owners_without_comment_author,
        private readonly string $comment,
        private readonly NotificationEnhancedContent $enhanced_content,
    ) {
    }

    public static function fromOwnersAndUpdatedComment(
        UserHelper $user_helper,
        HTMLURLBuilder $html_url_builder,
        FilterUserFromCollection $filter_user_from_collection,
        FormatNotificationContent $format_notification_content,
        PullRequest $pull_request,
        PFUser $comment_author,
        array $owners,
        Comment $comment,
    ): self {
        $comment_author_name           = $user_helper->getDisplayNameFromUser($comment_author) ?? '';
        $owners_without_comment_author = $filter_user_from_collection->filter($comment_author, ...$owners);

        return new self(
            $pull_request,
            $comment_author_name,
            $owners_without_comment_author,
            $comment->getContent(),
            new NotificationTemplatedContent(
                TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../../../templates/comment'),
                'update-comment-mail-content',
                new UpdatedCommentContentPresenter(
                    $user_helper->getAbsoluteUserURL($comment_author),
                    $comment_author_name,
                    $pull_request->getId(),
                    $html_url_builder->getAbsolutePullRequestOverviewUrl($pull_request),
                    $pull_request->getTitle(),
                    $format_notification_content->getFormattedAndPurifiedNotificationContent($pull_request, $comment),
                )
            )
        );
    }

    public function getPullRequest(): PullRequest
    {
        return $this->pull_request;
    }

    public function getRecipients(): array
    {
        return $this->owners_without_comment_author;
    }

    public function asPlaintext(): string
    {
        return sprintf(
            dgettext('tuleap-pullrequest', '%s updated their comment on #%d: %s.'),
            $this->comment_author_name,
            $this->pull_request->getId(),
            $this->pull_request->getTitle(),
        ) . "\n\n" . $this->comment;
    }

    public function asEnhancedContent(): NotificationEnhancedContent
    {
        return $this->enhanced_content;
    }
}
