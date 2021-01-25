<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository\Webhook;

use Psr\Log\LoggerInterface;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Cryptography\Symmetric\SymmetricCrypto;
use Tuleap\Gitlab\API\ClientWrapper;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\InstanceBaseURLBuilder;

class WebhookCreator
{
    /**
     * @var ClientWrapper
     */
    private $gitlab_api_client;
    /**
     * @var KeyFactory
     */
    private $key_factory;
    /**
     * @var WebhookDao
     */
    private $dao;
    /**
     * @var InstanceBaseURLBuilder
     */
    private $instance_base_url;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        KeyFactory $key_factory,
        WebhookDao $dao,
        ClientWrapper $gitlab_api_client,
        InstanceBaseURLBuilder $instance_base_url,
        LoggerInterface $logger
    ) {
        $this->gitlab_api_client = $gitlab_api_client;
        $this->key_factory       = $key_factory;
        $this->dao               = $dao;
        $this->instance_base_url = $instance_base_url;
        $this->logger            = $logger;
    }

    /**
     * @throws WebhookCreationException
     * @throws \Tuleap\Gitlab\API\GitlabRequestException
     * @throws \Tuleap\Gitlab\API\GitlabResponseAPIException
     */
    public function generateWebhookInGitlabProject(Credentials $credentials, GitlabRepository $gitlab_repository): void
    {
        $this->deletePreviousGitlabWebhook($credentials, $gitlab_repository);
        $this->createNewGitlabWebhook($credentials, $gitlab_repository);
    }

    /**
     * @throws WebhookCreationException
     * @throws \Tuleap\Gitlab\API\GitlabRequestException
     * @throws \Tuleap\Gitlab\API\GitlabResponseAPIException
     */
    private function createNewGitlabWebhook(
        Credentials $credentials,
        GitlabRepository $gitlab_repository
    ): void {
        $secret = new ConcealedString(\sodium_bin2hex(\random_bytes(32)));

        $webhook_id = $this->askGitlabToCreateANewWebhook($credentials, $gitlab_repository, $secret);

        $this->dao->storeWebhook(
            $gitlab_repository->getId(),
            $webhook_id,
            SymmetricCrypto::encrypt($secret, $this->key_factory->getEncryptionKey())
        );
    }

    /**
     * @throws WebhookCreationException
     * @throws \Tuleap\Gitlab\API\GitlabRequestException
     * @throws \Tuleap\Gitlab\API\GitlabResponseAPIException
     */
    private function askGitlabToCreateANewWebhook(
        Credentials $credentials,
        GitlabRepository $gitlab_repository,
        ConcealedString $secret
    ): int {
        $base_url = $this->instance_base_url->build();

        $gitlab_repository_id = $gitlab_repository->getGitlabRepositoryId();

        $webhook_configuration = [
            'url'                     => "$base_url/plugins/gitlab/repository/webhook",
            'token'                   => $secret->getString(),
            'push_events'             => true,
            'merge_requests_events'   => true,
            'enable_ssl_verification' => true
        ];

        $this->logger->info("Creating new hook for " . $gitlab_repository->getGitlabRepositoryUrl());

        $webhook = $this->gitlab_api_client->postUrl(
            $credentials,
            "/projects/$gitlab_repository_id/hooks",
            $webhook_configuration
        );
        \sodium_memzero($webhook_configuration['token']);


        if (! is_array($webhook) || ! isset($webhook['id'])) {
            $this->logger->error("Received response payload seems invalid");
            throw new WebhookCreationException();
        }

        return (int) $webhook['id'];
    }

    private function deletePreviousGitlabWebhook(
        Credentials $credentials,
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
