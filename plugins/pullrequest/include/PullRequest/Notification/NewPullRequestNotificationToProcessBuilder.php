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

use Tuleap\Git\RetrieveGitRepository;
use Tuleap\Markdown\ContentInterpretor;
use Tuleap\Notification\Mention\MentionedUserInTextRetriever;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use Tuleap\User\RetrieveUserById;
use UserHelper;

/**
 * @template-implements NotificationToProcessBuilder<NewPullRequestEvent>
 */
final readonly class NewPullRequestNotificationToProcessBuilder implements NotificationToProcessBuilder
{
    public function __construct(
        private PullRequestRetriever $pull_request_retriever,
        private RetrieveUserById $user_retriever,
        private RetrieveGitRepository $repository_retriever,
        private MentionedUserInTextRetriever $mentioned_user_retriever,
        private FilterUserFromCollection $filter_user_from_collection,
        private HTMLURLBuilder $html_url_builder,
        private ContentInterpretor $content_interpretor,
    ) {
    }

    #[\Override]
    public function getNotificationsToProcess(EventSubjectToNotification $event): array
    {
        return $this->pull_request_retriever->getPullRequestById($event->getPullRequestId())->match(
            function (PullRequest $pull_request) {
                $owner = $this->user_retriever->getUserById($pull_request->getUserId());
                if ($owner === null) {
                    return [];
                }
                $repository = $this->repository_retriever->getRepositoryById($pull_request->getRepositoryId());
                if ($repository === null) {
                    return [];
                }

                return [
                    NewPullRequestNotification::fromPullRequest(
                        $pull_request,
                        $repository,
                        $this->mentioned_user_retriever->getMentionedUsers($pull_request->getDescription()),
                        $this->filter_user_from_collection,
                        UserHelper::instance(),
                        $owner,
                        $this->html_url_builder,
                        $this->content_interpretor,
                    ),
                ];
            },
            fn() => [],
        );
    }
}
