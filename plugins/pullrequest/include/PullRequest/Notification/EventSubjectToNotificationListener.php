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

namespace Tuleap\PullRequest\Notification;

use Tuleap\PullRequest\Notification\Strategy\PullRequestNotificationStrategy;

/**
 * @psalm-type AcceptableNotificationToProcessBuilder = \Tuleap\PullRequest\Reviewer\Notification\ReviewerChangeNotificationToProcessBuilder |
 *                                                      \Tuleap\PullRequest\StateStatus\PullRequestAbandonedNotificationToProcessBuilder |
 *                                                      \Tuleap\PullRequest\StateStatus\PullRequestMergedNotificationToProcessBuilder |
 *                                                      \Tuleap\PullRequest\BranchUpdate\PullRequestUpdatedNotificationToProcessBuilder |
 *                                                      \Tuleap\PullRequest\Comment\Notification\PullRequestNewCommentNotificationToProcessBuilder |
 *                                                      \Tuleap\PullRequest\Comment\Notification\UpdatedCommentNotificationToProcessBuilder |
 *                                                      \Tuleap\PullRequest\InlineComment\Notification\PullRequestNewInlineCommentNotificationToProcessBuilder
 */
final class EventSubjectToNotificationListener
{
    /**
     * @var PullRequestNotificationStrategy
     * @psalm-readonly
     */
    private $strategy;
    /**
     * @var NotificationToProcessBuilder
     * @psalm-readonly
     */
    private $builder;

    /**
     * @psalm-param AcceptableNotificationToProcessBuilder $builder
     */
    public function __construct(PullRequestNotificationStrategy $strategy, NotificationToProcessBuilder $builder)
    {
        $this->strategy = $strategy;
        $this->builder  = $builder;
    }

    /**
     * @psalm-mutation-free
     */
    public function getNotificationStrategy(): PullRequestNotificationStrategy
    {
        return $this->strategy;
    }

    /**
     * @psalm-mutation-free
     */
    public function getNotificationToProcessBuilder(): NotificationToProcessBuilder
    {
        return $this->builder;
    }
}
