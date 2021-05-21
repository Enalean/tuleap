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

namespace Tuleap\Gitlab\Repository\Webhook\Bot;

use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\Token\IntegrationApiTokenRetriever;

class CredentialsRetriever
{
    /**
     * @var IntegrationApiTokenRetriever
     */
    private $token_retriever;

    public function __construct(IntegrationApiTokenRetriever $token_retriever)
    {
        $this->token_retriever = $token_retriever;
    }

    public function getCredentials(GitlabRepositoryIntegration $repository_integration): ?Credentials
    {
        $token = $this->token_retriever->getIntegrationAPIToken($repository_integration);

        if (! $token) {
            return null;
        }

        return new Credentials($repository_integration->getGitlabServerUrl(), $token);
    }
}
