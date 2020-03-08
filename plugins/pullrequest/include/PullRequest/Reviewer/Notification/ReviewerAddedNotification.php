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

namespace Tuleap\PullRequest\Reviewer\Notification;

use PFUser;
use TemplateRendererFactory;
use Tuleap\PullRequest\Notification\NotificationTemplatedContent;
use Tuleap\PullRequest\Notification\NotificationEnhancedContent;
use Tuleap\PullRequest\Notification\NotificationToProcess;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use UserHelper;

final class ReviewerAddedNotification implements NotificationToProcess
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
     * @var PFUser[]
     * @psalm-readonly
     */
    private $new_reviewers;
    /**
     * @var NotificationEnhancedContent
     * @psalm-readonly
     */
    private $enhanced_content;

    /**
     * @param PFUser[] $new_reviewers
     */
    private function __construct(
        PullRequest $pull_request,
        string $change_user_display_name,
        array $new_reviewers,
        NotificationEnhancedContent $enhanced_content
    ) {
        $this->pull_request              = $pull_request;
        $this->change_user_display_name  = $change_user_display_name;
        $this->new_reviewers             = $new_reviewers;
        $this->enhanced_content          = $enhanced_content;
    }

    /**
     * @psalm-param non-empty-array<PFUser> $new_reviewers
     */
    public static function fromReviewerChangeInformation(
        UserHelper $user_helper,
        HTMLURLBuilder $html_url_builder,
        PullRequest $pull_request,
        PFUser $change_user,
        array $new_reviewers
    ): self {
        $reviewers_to_notify = [];

        foreach ($new_reviewers as $new_reviewer) {
            if ($new_reviewer->getId() !== $change_user->getId()) {
                $reviewers_to_notify[] = $new_reviewer;
            }
        }

        $change_user_display_name = $user_helper->getDisplayNameFromUser($change_user) ?? '';

        return new self(
            $pull_request,
            $change_user_display_name,
            $reviewers_to_notify,
            new NotificationTemplatedContent(
                TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../../../templates/reviewer'),
                'reviewer-added-mail-content',
                new ReviewerAddedNotificationContentPresenter(
                    $change_user_display_name,
                    $user_helper->getAbsoluteUserURL($change_user),
                    $pull_request->getId(),
                    $pull_request->getTitle(),
                    $html_url_builder->getAbsolutePullRequestOverviewUrl($pull_request)
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
        return $this->new_reviewers;
    }

    /**
     * @psalm-mutation-free
     */
    public function asPlaintext(): string
    {
        return sprintf(
            dgettext('tuleap-pullrequest', '%s requested your review on #%d: %s'),
            $this->change_user_display_name,
            $this->pull_request->getId(),
            $this->pull_request->getTitle(),
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
