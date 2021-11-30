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
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;

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
        LoggerInterface $logger,
    ) {
        $this->gitlab_api_client = $gitlab_api_client;
        $this->dao               = $dao;
        $this->logger            = $logger;
    }

    public function deleteGitlabWebhookFromGitlabRepository(
        ?Credentials $credentials,
        GitlabRepositoryIntegration $gitlab_repository_integration,
    ): void {
        $integration_id = $gitlab_repository_integration->getId();

        $row = $this->dao->getGitlabRepositoryWebhook($integration_id);
        if (! $row) {
            return;
        }

        $previous_webhook_id = $row['gitlab_webhook_id'];
        if (! $previous_webhook_id) {
            return;
        }

        if (! $credentials) {
            $this->dao->deleteGitlabRepositoryWebhook($integration_id);
            return;
        }

        $this->logger->info("Deleting previous hook for " . $gitlab_repository_integration->getGitlabRepositoryUrl());

        $gitlab_repository_id = $gitlab_repository_integration->getGitlabRepositoryId();
        try {
            $this->gitlab_api_client->deleteUrl(
                $credentials,
                "/projects/$gitlab_repository_id/hooks/$previous_webhook_id"
            );
            $this->dao->deleteGitlabRepositoryWebhook($integration_id);

            if ($this->dao->isIntegrationWebhookUsedByIntegrations($previous_webhook_id)) {
                $this->logger->warning(
                    "The webhook is used by another integrations (it may come from old integration). " .
                    "It will be deleted on GitLab side and configuration must be regenerated for these integrations."
                );
                $this->dao->deleteAllGitlabRepositoryWebhookConfigurationUsingOldOne($previous_webhook_id);
            }
        } catch (GitlabRequestException $e) {
            // Ignore errors. It is not big deal if we cannot remove the hook.
            // Maybe it has already been manually deleted on GitLab side?
            $this->logger->info("Unable to delete the hook. Ignoring error: " . $e->getMessage());
        }
    }
}
