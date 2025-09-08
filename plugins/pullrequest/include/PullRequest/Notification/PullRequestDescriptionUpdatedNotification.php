<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Notification;

use GitRepository;
use PFUser;
use TemplateRendererFactory;
use Tuleap\Markdown\ContentInterpretor;
use Tuleap\Notification\Mention\MentionedUserCollection;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use UserHelper;

final readonly class PullRequestDescriptionUpdatedNotification implements NotificationToProcess
{
    /**
     * @param PFUser[] $recipients
     */
    private function __construct(
        private PullRequest $pull_request,
        private array $recipients,
        private string $plain_text,
        private NotificationEnhancedContent $enhanced_content,
    ) {
    }

    public static function fromPullRequest(
        PullRequest $pull_request,
        GitRepository $repository,
        MentionedUserCollection $mentioned_user_collection,
        FilterUserFromCollection $filter_user_from_collection,
        UserHelper $user_helper,
        PFUser $updater,
        HTMLURLBuilder $html_url_builder,
        ContentInterpretor $content_interpretor,
    ): self {
        $updater_display_name = $user_helper->getDisplayNameFromUser($updater);
        return new self(
            $pull_request,
            $filter_user_from_collection->filter($updater, ...$mentioned_user_collection->users),
            sprintf(
                dgettext('tuleap-pullrequest', '%s updated the description of the pull request #%d: %s'),
                $updater_display_name,
                $pull_request->getId(),
                $pull_request->getTitle(),
            ),
            new NotificationTemplatedContent(
                TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../../templates'),
                'pull-request-description-updated-mail-content',
                new NewPullRequestMailContentPresenter(
                    $user_helper->getAbsoluteUserURL($updater),
                    $updater_display_name,
                    $html_url_builder->getAbsolutePullRequestOverviewUrl($pull_request),
                    $pull_request->getId(),
                    $pull_request->getTitle(),
                    $content_interpretor->getInterpretedContentWithReferences($pull_request->getDescription(), (int) $repository->getId()),
                ),
            ),
        );
    }

    #[\Override]
    public function getPullRequest(): PullRequest
    {
        return $this->pull_request;
    }

    #[\Override]
    public function getRecipients(): array
    {
        return $this->recipients;
    }

    #[\Override]
    public function asPlaintext(): string
    {
        return $this->plain_text;
    }

    #[\Override]
    public function asEnhancedContent(): NotificationEnhancedContent
    {
        return $this->enhanced_content;
    }
}
