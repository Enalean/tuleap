<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\OAuth2Server\E2E\RelyingPartyOIDC;

use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Client\Common\PluginClient;
use Http\Message\Authentication\BasicAuth;
use Psr\Http\Client\ClientInterface;

final class OAuth2TestFlowHTTPClientWithClientCredentialFactory
{
    /**
     * @var ClientInterface
     */
    private $client;
    /**
     * @var OAuth2TestFlowClientCredentialStorage
     */
    private $client_credential_storage;

    public function __construct(ClientInterface $client, OAuth2TestFlowClientCredentialStorage $client_credential_storage)
    {
        $this->client                    = $client;
        $this->client_credential_storage = $client_credential_storage;
    }

    public function getHTTPClient(): ClientInterface
    {
        $client_id     = $this->client_credential_storage->getClientId();
        $client_secret = $this->client_credential_storage->getClientSecret();
        if ($client_id === null || $client_secret === null) {
            throw new RuntimeException('Client ID and secret are missing, did you call GET /init-flow first?');
        }
        return new PluginClient(
            $this->client,
            [
                new AuthenticationPlugin(
                    new BasicAuth($client_id, $client_secret)
                )
            ]
        );
    }
}
