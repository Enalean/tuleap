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

namespace Tuleap\PullRequest\StateStatus;

use PFUser;
use TemplateRendererFactory;
use Tuleap\PullRequest\Notification\FilterUserFromCollection;
use Tuleap\PullRequest\Notification\NotificationEnhancedContent;
use Tuleap\PullRequest\Notification\NotificationTemplatedContent;
use Tuleap\PullRequest\Notification\NotificationToProcess;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use UserHelper;

final class PullRequestMergedNotification implements NotificationToProcess
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
    private $owners;
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
        NotificationEnhancedContent $enhanced_content,
    ) {
        $this->pull_request             = $pull_request;
        $this->change_user_display_name = $change_user_display_name;
        $this->owners                   = $owners_without_change_user;
        $this->enhanced_content         = $enhanced_content;
    }

    /**
     * @param PFUser[] $owners
     */
    public static function fromOwners(
        UserHelper $user_helper,
        HTMLURLBuilder $html_url_builder,
        FilterUserFromCollection $filter_user_from_collection,
        PullRequest $pull_request,
        PFUser $change_user,
        array $owners,
    ): self {
        $change_user_display_name   = $user_helper->getDisplayNameFromUser($change_user) ?? '';
        $owners_without_change_user = $filter_user_from_collection->filter($change_user, ...$owners);

        return new self(
            $pull_request,
            $change_user_display_name,
            $owners_without_change_user,
            new NotificationTemplatedContent(
                TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../../templates/state-status'),
                'pull-request-merged-mail-content',
                new PullRequestMergedContentPresenter(
                    $change_user_display_name,
                    $user_helper->getAbsoluteUserURL($change_user),
                    $pull_request->getId(),
                    $pull_request->getTitle(),
                    $html_url_builder->getAbsolutePullRequestOverviewUrl($pull_request),
                    $pull_request->getBranchDest()
                )
            )
        );
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function getPullRequest(): PullRequest
    {
        return $this->pull_request;
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function getRecipients(): array
    {
        return $this->owners;
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function asPlaintext(): string
    {
        return sprintf(
            dgettext('tuleap-pullrequest', '%s has merged the pull request #%d: %s into %s'),
            $this->change_user_display_name,
            $this->pull_request->getId(),
            $this->pull_request->getTitle(),
            $this->pull_request->getBranchDest(),
        );
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function asEnhancedContent(): NotificationEnhancedContent
    {
        return $this->enhanced_content;
    }
}
