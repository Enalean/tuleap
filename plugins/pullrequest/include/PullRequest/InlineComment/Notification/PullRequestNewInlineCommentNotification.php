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

use PFUser;
use TemplateRendererFactory;
use Tuleap\Notification\Mention\MentionedUserCollection;
use Tuleap\PullRequest\InlineComment\InlineComment;
use Tuleap\PullRequest\Notification\FilterUserFromCollection;
use Tuleap\PullRequest\Notification\FormatNotificationContent;
use Tuleap\PullRequest\Notification\NotificationEnhancedContent;
use Tuleap\PullRequest\Notification\NotificationTemplatedContent;
use Tuleap\PullRequest\Notification\NotificationToProcess;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use UserHelper;

/**
 * @psalm-immutable
 */
final readonly class PullRequestNewInlineCommentNotification implements NotificationToProcess
{
    /**
     * @param PFUser[] $recipients_without_change_user
     */
    private function __construct(
        private PullRequest $pull_request,
        private string $change_user_display_name,
        private InlineComment $inline_comment,
        private string $code_context,
        private NotificationEnhancedContent $enhanced_content,
        private array $recipients_without_change_user,
    ) {
    }

    /**
     * @param PFUser[] $owners
     *
     * @throws InlineCommentCodeContextException
     */
    public static function fromOwnersAndInlineComment(
        UserHelper $user_helper,
        HTMLURLBuilder $html_url_builder,
        FilterUserFromCollection $filter_user_from_collection,
        PullRequest $pull_request,
        PFUser $change_user,
        array $owners,
        InlineComment $inline_comment,
        InlineCommentCodeContextExtractor $code_context_extractor,
        FormatNotificationContent $format_notification_content,
        MentionedUserCollection $mentioned_users,
    ): self {
        $code_context = $code_context_extractor->getCodeContext($inline_comment, $pull_request);

        $change_user_display_name       = $user_helper->getDisplayNameFromUser($change_user) ?? '';
        $recipients_without_change_user = $filter_user_from_collection->filter($change_user, ...$owners, ...$mentioned_users->users);

        return new self(
            $pull_request,
            $change_user_display_name,
            $inline_comment,
            $code_context,
            new NotificationTemplatedContent(
                TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../../../templates/comment'),
                'pull-request-new-inline-comment-mail-content',
                new PullRequestNewInlineCommentContentPresenter(
                    $change_user_display_name,
                    $user_helper->getAbsoluteUserURL($change_user),
                    $pull_request->getId(),
                    $pull_request->getTitle(),
                    $html_url_builder->getAbsolutePullRequestOverviewUrl($pull_request),
                    $format_notification_content->getFormattedAndPurifiedNotificationContent($pull_request, $inline_comment),
                    $inline_comment->getFilePath(),
                    $code_context,
                ),
            ),
            $recipients_without_change_user
        );
    }

    #[\Override]
    public function getPullRequest(): PullRequest
    {
        return $this->pull_request;
    }

    /**
     * @return \PFUser[]
     */
    #[\Override]
    public function getRecipients(): array
    {
        return $this->recipients_without_change_user;
    }

    #[\Override]
    public function asPlaintext(): string
    {
        return sprintf(
            dgettext('tuleap-pullrequest', "%s commented on #%d: %s in %s:\n\n%s\n\n%s"),
            $this->change_user_display_name,
            $this->pull_request->getId(),
            $this->pull_request->getTitle(),
            $this->inline_comment->getFilePath(),
            $this->code_context,
            $this->inline_comment->getContent()
        );
    }

    #[\Override]
    public function asEnhancedContent(): NotificationEnhancedContent
    {
        return $this->enhanced_content;
    }
}
