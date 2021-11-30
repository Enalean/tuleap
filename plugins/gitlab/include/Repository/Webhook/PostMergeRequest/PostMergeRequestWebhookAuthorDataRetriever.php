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

namespace Tuleap\Gitlab\Repository\Webhook\PostMergeRequest;

use Tuleap\Gitlab\API\ClientWrapper;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\Webhook\Bot\CredentialsRetriever;

class PostMergeRequestWebhookAuthorDataRetriever
{
    /**
     * @var ClientWrapper
     */
    private $gitlab_api_client;
    /**
     * @var CredentialsRetriever
     */
    private $credentials_retriever;

    public function __construct(
        ClientWrapper $gitlab_api_client,
        CredentialsRetriever $credentials_retriever,
    ) {
        $this->gitlab_api_client     = $gitlab_api_client;
        $this->credentials_retriever = $credentials_retriever;
    }

    /**
     * @throws GitlabRequestException
     * @throws GitlabResponseAPIException
     */
    public function retrieveAuthorData(
        GitlabRepositoryIntegration $gitlab_repository_integration,
        PostMergeRequestWebhookData $webhook_data,
    ): ?array {
        $credentials = $this->credentials_retriever->getCredentials($gitlab_repository_integration);

        if (! $credentials) {
            return null;
        }

        return $this->gitlab_api_client->getUrl($credentials, "/users/{$webhook_data->getAuthorId()}");
    }
}
