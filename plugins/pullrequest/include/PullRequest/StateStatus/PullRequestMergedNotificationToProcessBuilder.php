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

use Tuleap\PullRequest\Exception\PullRequestNotFoundException;
use Tuleap\PullRequest\Factory;
use Tuleap\PullRequest\Notification\EventSubjectToNotification;
use Tuleap\PullRequest\Notification\FilterUserFromCollection;
use Tuleap\PullRequest\Notification\NotificationToProcessBuilder;
use Tuleap\PullRequest\Notification\OwnerRetriever;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use UserHelper;
use UserManager;

/**
 * @template-implements NotificationToProcessBuilder<PullRequestMergedEvent>
 */
final class PullRequestMergedNotificationToProcessBuilder implements NotificationToProcessBuilder
{
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var Factory
     */
    private $pull_request_factory;
    /**
     * @var OwnerRetriever
     */
    private $owner_retriever;
    /**
     * @var FilterUserFromCollection
     */
    private $filter_user_from_collection;
    /**
     * @var UserHelper
     */
    private $user_helper;
    /**
     * @var HTMLURLBuilder
     */
    private $html_url_builder;

    public function __construct(
        UserManager $user_manager,
        Factory $pull_request_factory,
        OwnerRetriever $owner_retriever,
        FilterUserFromCollection $filter_user_from_collection,
        UserHelper $user_helper,
        HTMLURLBuilder $html_url_builder
    ) {
        $this->user_manager                = $user_manager;
        $this->pull_request_factory        = $pull_request_factory;
        $this->owner_retriever             = $owner_retriever;
        $this->filter_user_from_collection = $filter_user_from_collection;
        $this->user_helper                 = $user_helper;
        $this->html_url_builder            = $html_url_builder;
    }

    public function getNotificationsToProcess(EventSubjectToNotification $event): array
    {
        try {
            $pull_request = $this->pull_request_factory->getPullRequestById($event->getPullRequestID());
        } catch (PullRequestNotFoundException $e) {
            return [];
        }

        $change_user = $this->user_manager->getUserById($event->getUserID());
        if ($change_user === null) {
            return [];
        }

        $pull_request_owners = $this->owner_retriever->getOwners($pull_request);

        return [
            PullRequestMergedNotification::fromOwners(
                $this->user_helper,
                $this->html_url_builder,
                $this->filter_user_from_collection,
                $pull_request,
                $change_user,
                $pull_request_owners
            )
        ];
    }
}
