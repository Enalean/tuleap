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

use ForgeConfig;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Gitlab\API\ClientWrapper;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\Gitlab\Repository\Webhook\Secret\SecretGenerator;

class WebhookCreator
{
    /**
     * @var SecretGenerator
     */
    private $secret_generator;

    /**
     * @var ClientWrapper
     */
    private $gitlab_api_client;

    public function __construct(SecretGenerator $secret_generator, ClientWrapper $gitlab_api_client)
    {
        $this->secret_generator  = $secret_generator;
        $this->gitlab_api_client = $gitlab_api_client;
    }

    public function addWebhookInGitlabProject(Credentials $credentials, GitlabRepository $gitlab_repository): void
    {
        $secret = $this->secret_generator->generateSecretForGitlabRepository($gitlab_repository->getId());

        $webhook_configuration_content = $this->generateWebhookConfiguration($secret);

        $gitlab_repository_id = $gitlab_repository->getGitlabRepositoryId();
        $this->gitlab_api_client->postUrl(
            $credentials,
            "/projects/$gitlab_repository_id/hooks",
            $webhook_configuration_content
        );

        \sodium_memzero($webhook_configuration_content['token']);
    }

    private function generateWebhookConfiguration(ConcealedString $secret): array
    {
        return [
            'url'   => "https://" . ForgeConfig::get('sys_https_host') . "/plugins/gitlab/repository/webhook",
            'token' => $secret->getString(),
            'push_events' => true,
            'merge_requests_events' => true,
            'enable_ssl_verification' => true
        ];
    }
}
