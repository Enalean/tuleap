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

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

final class OAuth2InitFlowController
{
    /**
     * @var OAuth2TestFlowSecretGenerator
     */
    private $secret_generator;
    /**
     * @var OAuth2TestFlowClientCredentialStorage
     */
    private $client_credential_storage;
    /**
     * @var ClientInterface
     */
    private $http_client;
    /**
     * @var RequestFactoryInterface
     */
    private $request_factory;
    /**
     * @var OAuth2TestFlowConfigurationStorage
     */
    private $configuration_storage;

    public function __construct(
        OAuth2TestFlowSecretGenerator $secret_generator,
        OAuth2TestFlowClientCredentialStorage $client_credential_storage,
        ClientInterface $http_client,
        RequestFactoryInterface $request_factory,
        OAuth2TestFlowConfigurationStorage $configuration_storage
    ) {
        $this->secret_generator          = $secret_generator;
        $this->client_credential_storage = $client_credential_storage;
        $this->http_client               = $http_client;
        $this->request_factory           = $request_factory;
        $this->configuration_storage     = $configuration_storage;
    }

    public function __invoke(Request $request): Response
    {
        parse_str($request->getUri()->getQuery(), $query_params);

        // ⚠️ If you are reading this as an example (please don't) on how to
        // ⚠️ write an OAuth2.0 client or an OIDC relying party please not that the
        // ⚠️ client secret MUST NOT be exposed to the End-User
        // ⚠️ Here it is done this way for the sack of the test, i.e. to have the capability
        // ⚠️ to use a client dynamically register by project admin bot user.
        // ⚠️ Doing this in the real life breaks some security assumptions.
        $client_id     = $query_params['client_id'] ?? null;
        $client_secret = $query_params['client_secret'] ?? null;

        if ($client_id === null || $client_secret === null) {
            return new Response(
                Status::BAD_REQUEST,
                ['Content-Type' => 'text/plain'],
                'Missing client_id or client_secret'
            );
        }
        $this->client_credential_storage->setCredentials($client_id, $client_secret);

        $endpoints = $this->discoverEndpoints();

        $redirect_parameters = [
            'response_type'         => 'code',
            'client_id'             => $client_id,
            'client_secret'         => $client_secret,
            'scope'                 => 'openid offline_access profile',
            'redirect_uri'          => OAuth2TestFlowConstants::REDIRECT_URI,
            'state'                 => $this->secret_generator->getState(),
            'nonce'                 => $this->secret_generator->getNonce(),
            'code_challenge'        => sodium_bin2base64(hash('sha256', $this->secret_generator->getPKCEChallenge(), true), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING),
            'code_challenge_method' => 'S256'
        ];
        return new Response(
            Status::FOUND,
            ['Location' => $endpoints->getAuthorizationEndpoint() . '?' . http_build_query($redirect_parameters)]
        );
    }

    private function discoverEndpoints(): OAuth2TestFlowConfiguration
    {
        $response  = $this->http_client->sendRequest(
            $this->request_factory->createRequest('GET', OAuth2TestFlowConstants::DISCOVERY_ENDPOINT)
        );
        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException(
                'Failed to retrieve endpoints configuration %d %s',
                $response->getStatusCode(),
                $response->getBody()->getContents()
            );
        }

        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $configuration = new OAuth2TestFlowConfiguration(
            $json['authorization_endpoint'],
            $json['token_endpoint'],
            $json['userinfo_endpoint'],
            $json['jwks_uri']
        );
        $this->configuration_storage->setConfiguration($configuration);
        return $configuration;
    }
}
