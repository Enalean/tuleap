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
use Tuleap\PullRequest\InlineComment\InlineComment;
use Tuleap\PullRequest\Notification\FilterUserFromCollection;
use Tuleap\PullRequest\Notification\FormatNotificationContent;
use Tuleap\PullRequest\Notification\NotificationEnhancedContent;
use Tuleap\PullRequest\Notification\NotificationTemplatedContent;
use Tuleap\PullRequest\Notification\NotificationToProcess;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use UserHelper;

final class PullRequestNewInlineCommentNotification implements NotificationToProcess
{
    /**
     * @var PullRequest
     * @psalm-readonly
     */
    private $pull_request;
    /**
     * @var string
     * @psalm-readonly
     */
    private $change_user_display_name;
    /**
     * @var array
     * @psalm-readonly
     */
    private $owners;
    /**
     * @var InlineComment
     * @psalm-readonly
     */
    private $inline_comment;
    /**
     * @var string
     * @psalm-readonly
     */
    private $code_context;
    /**
     * @var NotificationEnhancedContent
     * @psalm-readonly
     */
    private $enhanced_content;

    /**
     * @param PFUser[] $owners_without_change_user
     */
    private function __construct(
        PullRequest $pull_request,
        string $change_user_display_name,
        array $owners_without_change_user,
        InlineComment $inline_comment,
        string $code_context,
        NotificationEnhancedContent $enhanced_content,
    ) {
        $this->pull_request             = $pull_request;
        $this->change_user_display_name = $change_user_display_name;
        $this->owners                   = $owners_without_change_user;
        $this->inline_comment           = $inline_comment;
        $this->code_context             = $code_context;
        $this->enhanced_content         = $enhanced_content;
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
    ): self {
        $code_context = $code_context_extractor->getCodeContext($inline_comment, $pull_request);

        $change_user_display_name   = $user_helper->getDisplayNameFromUser($change_user) ?? '';
        $owners_without_change_user = $filter_user_from_collection->filter($change_user, ...$owners);

        return new self(
            $pull_request,
            $change_user_display_name,
            $owners_without_change_user,
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
                )
            )
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function getPullRequest(): PullRequest
    {
        return $this->pull_request;
    }

    /**
     * @psalm-mutation-free
     */
    public function getRecipients(): array
    {
        return $this->owners;
    }

    /**
     * @psalm-mutation-free
     */
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

    /**
     * @psalm-mutation-free
     */
    public function asEnhancedContent(): NotificationEnhancedContent
    {
        return $this->enhanced_content;
    }
}
