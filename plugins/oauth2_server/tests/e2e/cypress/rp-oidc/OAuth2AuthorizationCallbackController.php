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
use Psr\Http\Message\StreamFactoryInterface;

final class OAuth2AuthorizationCallbackController
{
    /**
     * @var OAuth2TestFlowSecretGenerator
     */
    private $secret_generator;
    /**
     * @var ClientInterface
     */
    private $http_client;
    /**
     * @var ClientInterface
     */
    private $http_client_with_client_credential_factory;
    /**
     * @var RequestFactoryInterface
     */
    private $request_factory;
    /**
     * @var StreamFactoryInterface
     */
    private $stream_factory;

    public function __construct(
        OAuth2TestFlowSecretGenerator $secret_generator,
        ClientInterface $http_client,
        OAuth2TestFlowHTTPClientWithClientCredentialFactory $http_client_factory,
        RequestFactoryInterface $request_factory,
        StreamFactoryInterface $stream_factory
    ) {
        $this->secret_generator                           = $secret_generator;
        $this->http_client                                = $http_client;
        $this->http_client_with_client_credential_factory = $http_client_factory;
        $this->request_factory                            = $request_factory;
        $this->stream_factory                             = $stream_factory;
    }

    public function __invoke(Request $request): Response
    {
        parse_str($request->getUri()->getQuery(), $query_params);
        if (! hash_equals($this->secret_generator->getState(), $query_params['state'] ?? '')) {
            return new Response(Status::BAD_REQUEST, ['Content-Type' => 'text/html'], 'Failure, state does not match');
        }

        $auth_code = $query_params['code'] ?? null;
        if ($auth_code === null) {
            return new Response(Status::BAD_REQUEST, ['Content-Type' => 'text/html'], 'Failure, code parameter was not returned by the OAuth2 provider');
        }

        $http_client_with_client_credentials = $this->http_client_with_client_credential_factory->getHTTPClient();

        $access_token_response  = $this->exchangeAuthorizationCode(
            $http_client_with_client_credentials,
            $auth_code
        );
        $refresh_token_response = $this->refreshTokens(
            $http_client_with_client_credentials,
            $access_token_response['refresh_token']
        );
        $user_info_response = $this->getUserInfo($refresh_token_response['access_token']);

        return new Response(Status::OK, ['Content-Type' => 'text/html'], 'OK as ' . $user_info_response['preferred_username']);
    }

    /**
     * @psalm-return array{access_token:string,refresh_token:string,id_token:string}
     */
    private function exchangeAuthorizationCode(ClientInterface $http_client_with_client_credential, string $auth_code): array
    {
        $response = $http_client_with_client_credential->sendRequest(
            $this->request_factory->createRequest('POST', OAuth2TestFlowConstants::BASE_CLIENT_URI . '/oauth2/token')
                ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->withBody(
                    $this->stream_factory->createStream(
                        http_build_query(
                            [
                                'grant_type'    => 'authorization_code',
                                'redirect_uri'  => OAuth2TestFlowConstants::REDIRECT_URI,
                                'code_verifier' => $this->secret_generator->getPKCEChallenge(),
                                'code'          => $auth_code
                            ]
                        )
                    )
                )
        );

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException(
                sprintf(
                    'Failed to exchange the authorization code %d %s',
                    $response->getStatusCode(),
                    $response->getBody()->getContents()
                )
            );
        }

        return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @psalm-return array{access_token:string,refresh_token:string}
     */
    private function refreshTokens(ClientInterface $http_client_with_client_credential, string $refresh_token): array
    {
        $response = $http_client_with_client_credential->sendRequest(
            $this->request_factory->createRequest('POST', OAuth2TestFlowConstants::BASE_CLIENT_URI . '/oauth2/token')
                ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->withBody(
                    $this->stream_factory->createStream(
                        http_build_query(
                            [
                                'grant_type'    => 'refresh_token',
                                'refresh_token' => $refresh_token
                            ]
                        )
                    )
                )
        );

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException(
                sprintf(
                    'Failed to refresh tokens %d %s',
                    $response->getStatusCode(),
                    $response->getBody()->getContents()
                )
            );
        }

        return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @psalm-return array{preferred_username:string}
     */
    private function getUserInfo(string $access_token): array
    {
        $response = $this->http_client->sendRequest(
            $this->request_factory->createRequest('GET', OAuth2TestFlowConstants::BASE_CLIENT_URI . '/oauth2/userinfo')
                ->withHeader('Authorization', 'Bearer ' . $access_token)
        );

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Failed to retrieve the user info');
        }

        return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }
}
