<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository\Webhook;

use Psr\Log\LoggerInterface;
use Tuleap\Gitlab\API\ClientWrapper;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\Repository\GitlabRepository;

class WebhookDeletor
{
    /**
     * @var ClientWrapper
     */
    private $gitlab_api_client;
    /**
     * @var WebhookDao
     */
    private $dao;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        WebhookDao $dao,
        ClientWrapper $gitlab_api_client,
        LoggerInterface $logger
    ) {
        $this->gitlab_api_client = $gitlab_api_client;
        $this->dao               = $dao;
        $this->logger            = $logger;
    }

    public function deleteGitlabWebhookFromGitlabRepository(
        ?Credentials $credentials,
        GitlabRepository $gitlab_repository
    ): void {
        $row = $this->dao->getGitlabRepositoryWebhook($gitlab_repository->getId());
        if (! $row) {
            return;
        }

        $previous_webhook_id = $row['gitlab_webhook_id'];
        if (! $previous_webhook_id) {
            return;
        }

        if (! $credentials) {
            $this->dao->deleteGitlabRepositoryWebhook($gitlab_repository->getId());
            return;
        }

        $this->logger->info("Deleting previous hook for " . $gitlab_repository->getGitlabRepositoryUrl());

        $gitlab_repository_id = $gitlab_repository->getGitlabRepositoryId();
        try {
            $this->gitlab_api_client->deleteUrl(
                $credentials,
                "/projects/$gitlab_repository_id/hooks/$previous_webhook_id"
            );
            $this->dao->deleteGitlabRepositoryWebhook($gitlab_repository->getId());
        } catch (GitlabRequestException $e) {
            // Ignore errors. It is not big deal if we cannot remove the hook.
            // Maybe it has already been manually deleted on GitLab side?
            $this->logger->info("Unable to delete the hook. Ignoring error: " . $e->getMessage());
        }
    }
}
