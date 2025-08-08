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
use Tuleap\Cryptography\SymmetricLegacy2025\SymmetricCrypto;
use Tuleap\Gitlab\API\ClientWrapper;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\ServerHostname;

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
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var WebhookDeletor
     */
    private $webhook_deletor;

    public function __construct(
        KeyFactory $key_factory,
        WebhookDao $dao,
        WebhookDeletor $webhook_deletor,
        ClientWrapper $gitlab_api_client,
        LoggerInterface $logger,
    ) {
        $this->gitlab_api_client = $gitlab_api_client;
        $this->key_factory       = $key_factory;
        $this->dao               = $dao;
        $this->webhook_deletor   = $webhook_deletor;
        $this->logger            = $logger;
    }

    /**
     * @throws WebhookCreationException
     * @throws \Tuleap\Gitlab\API\GitlabRequestException
     * @throws \Tuleap\Gitlab\API\GitlabResponseAPIException
     */
    public function generateWebhookInGitlabProject(
        Credentials $credentials,
        GitlabRepositoryIntegration $gitlab_repository_integration,
    ): void {
        $this->webhook_deletor->deleteGitlabWebhookFromGitlabRepository($credentials, $gitlab_repository_integration);
        $this->createNewGitlabWebhook($credentials, $gitlab_repository_integration);
    }

    /**
     * @throws WebhookCreationException
     * @throws \Tuleap\Gitlab\API\GitlabRequestException
     * @throws \Tuleap\Gitlab\API\GitlabResponseAPIException
     */
    private function createNewGitlabWebhook(
        Credentials $credentials,
        GitlabRepositoryIntegration $gitlab_repository_integration,
    ): void {
        $secret = new ConcealedString(\sodium_bin2hex(\random_bytes(32)));

        $webhook_id = $this->askGitlabToCreateANewWebhook($credentials, $gitlab_repository_integration, $secret);

        $this->dao->storeWebhook(
            $gitlab_repository_integration->getId(),
            $webhook_id,
            SymmetricCrypto::encrypt($secret, $this->key_factory->getLegacy2025EncryptionKey())
        );
    }

    /**
     * @throws WebhookCreationException
     * @throws \Tuleap\Gitlab\API\GitlabRequestException
     * @throws \Tuleap\Gitlab\API\GitlabResponseAPIException
     */
    private function askGitlabToCreateANewWebhook(
        Credentials $credentials,
        GitlabRepositoryIntegration $gitlab_repository_integration,
        ConcealedString $secret,
    ): int {
        $base_url = ServerHostname::HTTPSUrl();

        $gitlab_repository_id = $gitlab_repository_integration->getGitlabRepositoryId();
        $integration_id       = $gitlab_repository_integration->getId();

        $webhook_configuration = [
            'url'                     => "$base_url/plugins/gitlab/integration/$integration_id/webhook",
            'token'                   => $secret->getString(),
            'push_events'             => true,
            'merge_requests_events'   => true,
            'tag_push_events'         => true,
            'enable_ssl_verification' => true,
        ];

        $this->logger->info('Creating new hook for ' . $gitlab_repository_integration->getGitlabRepositoryUrl());

        $webhook = $this->gitlab_api_client->postUrl(
            $credentials,
            "/projects/$gitlab_repository_id/hooks",
            $webhook_configuration
        );
        \sodium_memzero($webhook_configuration['token']);

        if (! is_array($webhook) || ! isset($webhook['id'])) {
            $this->logger->error('Received response payload seems invalid');
            throw new WebhookCreationException();
        }

        return (int) $webhook['id'];
    }
}
