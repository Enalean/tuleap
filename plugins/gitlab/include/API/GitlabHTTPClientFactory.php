<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Gitlab\API;

use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Message\Authentication\Bearer;
use Http\Client\Common\PluginClient;
use Psr\Http\Client\ClientInterface;

class GitlabHTTPClientFactory implements BuildGitlabHttpClient
{
    /**
     * @var ClientInterface
     */
    private $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    #[\Override]
    public function buildHTTPClient(Credentials $gitlab_credentials): PluginClient
    {
        return new PluginClient(
            $this->client,
            [
                new AuthenticationPlugin(
                    new Bearer($gitlab_credentials->getApiToken()->getToken()->getString())
                ),
            ]
        );
    }
}
