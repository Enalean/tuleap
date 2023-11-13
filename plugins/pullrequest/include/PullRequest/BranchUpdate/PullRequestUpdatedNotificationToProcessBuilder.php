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

namespace Tuleap\PullRequest\BranchUpdate;

use Git_GitRepositoryUrlManager;
use GitRepositoryFactory;
use Tuleap\Git\GitPHP\ProjectProvider;
use Tuleap\Git\GitPHP\RepositoryAccessException;
use Tuleap\PullRequest\Notification\EventSubjectToNotification;
use Tuleap\PullRequest\Notification\FilterUserFromCollection;
use Tuleap\PullRequest\Notification\NotificationToProcessBuilder;
use Tuleap\PullRequest\Notification\OwnerRetriever;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use Tuleap\User\RetrieveUserById;
use UserHelper;

/**
 * @template-implements NotificationToProcessBuilder<PullRequestUpdatedEvent>
 */
final class PullRequestUpdatedNotificationToProcessBuilder implements NotificationToProcessBuilder
{
    public function __construct(
        private readonly RetrieveUserById $user_manager,
        private readonly PullRequestRetriever $pull_request_retriever,
        private readonly GitRepositoryFactory $git_repository_factory,
        private readonly OwnerRetriever $owner_retriever,
        private readonly FilterUserFromCollection $filter_user_from_collection,
        private readonly UserHelper $user_helper,
        private readonly HTMLURLBuilder $html_url_builder,
        private readonly Git_GitRepositoryUrlManager $url_manager,
        private readonly PullRequestUpdateCommitDiff $commit_diff,
    ) {
    }

    public function getNotificationsToProcess(EventSubjectToNotification $event): array
    {
        $pull_request_result = $this->pull_request_retriever->getPullRequestById($event->getPullRequestID());
        return $pull_request_result->match(
            function (PullRequest $pull_request) use ($event) {
                $change_user = $this->user_manager->getUserById($event->getUserID());
                if ($change_user === null) {
                    return [];
                }

                $git_repository = $this->git_repository_factory->getRepositoryById($pull_request->getRepoDestId());
                if ($git_repository === null) {
                    return [];
                }

                try {
                    $git_resource_accessor_provider = new ProjectProvider($git_repository);
                } catch (RepositoryAccessException $exception) {
                    return [];
                }

                $git_exec = \Git_Exec::buildFromRepository($git_repository);
                try {
                    $new_commits = $this->commit_diff->findNewCommitReferences(
                        $git_exec,
                        $event->getOldSourceReference(),
                        $event->getNewSourceReference(),
                        $event->getOldDestinationReference(),
                        $event->getNewDestinationReference(),
                    );
                } catch (\Git_Command_Exception $exception) {
                    return [];
                }

                if (empty($new_commits)) {
                    return [];
                }

                $url_builder = new RepositoryURLToCommitBuilder($this->url_manager, $git_repository);

                $pull_request_owners = $this->owner_retriever->getOwners($pull_request);

                return array_filter(
                    [
                        PullRequestUpdatedNotification::fromOwnersAndReferences(
                            $this->user_helper,
                            $this->html_url_builder,
                            $this->filter_user_from_collection,
                            $pull_request,
                            $change_user,
                            $pull_request_owners,
                            $git_resource_accessor_provider->GetProject(),
                            $url_builder,
                            $new_commits
                        ),
                    ]
                );
            },
            fn() => []
        );
    }
}
