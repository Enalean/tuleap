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
use Tuleap\PullRequest\Notification\NotificationToProcess;
use Tuleap\PullRequest\PullRequest;
use UserHelper;

/**
 * @psalm-immutable
 */
final class ReviewerAddedNotification implements NotificationToProcess
{
    /**
     * @var PullRequest
     */
    private $pull_request;
    /**
     * @var string
     */
    private $change_user_display_name;
    /**
     * @var PFUser
     */
    private $new_reviewer;

    private function __construct(
        PullRequest $pull_request,
        string $change_user_display_name,
        PFUser $new_reviewer
    ) {
        $this->pull_request              = $pull_request;
        $this->change_user_display_name  = $change_user_display_name;
        $this->new_reviewer              = $new_reviewer;
    }

    public static function fromReviewerChangeInformation(
        UserHelper $user_helper,
        PullRequest $pull_request,
        PFUser $change_user,
        PFUser $new_reviewer
    ): self {
        return new self(
            $pull_request,
            $user_helper->getDisplayNameFromUser($change_user) ?? '',
            $new_reviewer
        );
    }

    public function getPullRequest(): PullRequest
    {
        return $this->pull_request;
    }

    public function getRecipients(): array
    {
        return [$this->new_reviewer];
    }

    public function asPlaintext(): string
    {
        return sprintf(
            dgettext('tuleap-pullrequest', '%s requested your review on #%d: %s'),
            $this->change_user_display_name,
            $this->pull_request->getId(),
            $this->pull_request->getTitle(),
        );
    }
}
