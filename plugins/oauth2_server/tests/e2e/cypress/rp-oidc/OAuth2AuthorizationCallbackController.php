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
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\ValidationData;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Tuleap\JWT\JWKS\PKCS1Format;

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
     * @var OAuth2TestFlowClientCredentialStorage
     */
    private $client_credential_storage;
    /**
     * @var OAuth2TestFlowConfigurationStorage
     */
    private $configuration_storage;
    /**
     * @var Parser
     */
    private $parser;
    /**
     * @var Sha256
     */
    private $signer;
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
        OAuth2TestFlowClientCredentialStorage $client_credential_storage,
        OAuth2TestFlowConfigurationStorage $configuration_storage,
        Parser $parser,
        Sha256 $signer,
        RequestFactoryInterface $request_factory,
        StreamFactoryInterface $stream_factory
    ) {
        $this->secret_generator                           = $secret_generator;
        $this->http_client                                = $http_client;
        $this->http_client_with_client_credential_factory = $http_client_factory;
        $this->client_credential_storage                  = $client_credential_storage;
        $this->configuration_storage                      = $configuration_storage;
        $this->parser                                     = $parser;
        $this->signer                                     = $signer;
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
        $sub = $this->validateIDToken($access_token_response['id_token']);
        $refresh_token_response = $this->refreshTokens(
            $http_client_with_client_credentials,
            $access_token_response['refresh_token']
        );

        $user_info_response = $this->getUserInfo($refresh_token_response['access_token']);
        if (! hash_equals($sub, $user_info_response['sub'])) {
            throw new \RuntimeException(sprintf('The user info sub (%s) does not match the ID token sub (%s)', $user_info_response['sub'], $sub));
        }

        $this->revoke($http_client_with_client_credentials, $refresh_token_response);

        return new Response(Status::OK, ['Content-Type' => 'text/html'], 'OK as ' . $user_info_response['preferred_username']);
    }

    /**
     * @psalm-return array{access_token:string,refresh_token:string,id_token:string}
     */
    private function exchangeAuthorizationCode(ClientInterface $http_client_with_client_credential, string $auth_code): array
    {
        $response = $http_client_with_client_credential->sendRequest(
            $this->request_factory->createRequest('POST', $this->configuration_storage->getConfiguration()->getTokenEndpoint())
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
            $this->request_factory->createRequest('POST', $this->configuration_storage->getConfiguration()->getTokenEndpoint())
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
        $response = $this->sendUserInfoRequest($access_token);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Failed to retrieve the user info');
        }

        return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function sendUserInfoRequest(string $access_token): ResponseInterface
    {
        return $this->http_client->sendRequest(
            $this->request_factory->createRequest('GET', $this->configuration_storage->getConfiguration()->getUserinfoEndpoint())
                ->withHeader('Authorization', 'Bearer ' . $access_token)
        );
    }

    private function validateIDToken(string $id_token_jwt): string
    {
        $id_token = $this->parser->parse($id_token_jwt);

        $public_keys        = $this->getPublicKeys();
        $is_signature_valid = false;
        foreach ($public_keys as $public_key) {
            $is_signature_valid = $is_signature_valid || $id_token->verify($this->signer, new Key($public_key));
        }
        if (! $is_signature_valid) {
            throw new \RuntimeException('Verification of the ID token signature has failed');
        }

        $validation_data = new ValidationData();
        $validation_data->setIssuer(OAuth2TestFlowConstants::BASE_CLIENT_URI);
        $validation_data->setAudience($this->client_credential_storage->getClientId());
        if (! $id_token->validate($validation_data)) {
            throw new \RuntimeException('Validation of ID token claims has failed');
        }

        $expected_nonce = $this->secret_generator->getNonce();
        $nonce_claim    = $id_token->getClaim('nonce', '');
        if (! hash_equals($expected_nonce, $nonce_claim)) {
            throw new \RuntimeException(
                sprintf('Nonce claim (%s) has failed does not have the expected value (%s)', $nonce_claim, $expected_nonce)
            );
        }

        return $id_token->getClaim('sub', '');
    }

    /**
     * @return string[]
     */
    private function getPublicKeys(): array
    {
        $response = $this->http_client->sendRequest(
            $this->request_factory->createRequest('GET', $this->configuration_storage->getConfiguration()->getJwksUri())
        );

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Failed to retrieve the JWKS document');
        }

        $jwks_document = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $keys = [];
        foreach ($jwks_document['keys'] as $key) {
            if ($key['alg'] === 'RS256') {
                $keys[] = PKCS1Format::convertFromRSAModulusAndExponent(
                    sodium_base642bin($key['n'], SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING),
                    sodium_base642bin($key['e'], SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING)
                );
            }
        }

        if (empty($keys)) {
            throw new \RuntimeException('No valid RS256 key found');
        }

        return $keys;
    }

    /**
     * @param string[] $token_response
     * @psalm-param array{access_token:string,refresh_token:string} $token_response
     */
    private function revoke(ClientInterface $http_client_with_client_credential, array $token_response): void
    {
        $revocation_response = $http_client_with_client_credential->sendRequest(
            $this->request_factory->createRequest('POST', OAuth2TestFlowConstants::BASE_CLIENT_URI . '/oauth2/token/revoke')
                ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->withBody(
                    $this->stream_factory->createStream(
                        http_build_query(['token' => $token_response['refresh_token']])
                    )
                )
        );

        if ($revocation_response->getStatusCode() !== 200) {
            throw new \RuntimeException(
                sprintf(
                    'Failed to revoke token %d %s',
                    $revocation_response->getStatusCode(),
                    $revocation_response->getBody()->getContents()
                )
            );
        }

        $user_info_response = $this->sendUserInfoRequest($token_response['access_token']);
        if ($user_info_response->getStatusCode() !== 401) {
            throw new \RuntimeException(
                'Expected to get an Unauthorized error when calling userinfo endpoint with a revoked token, got code ' . $user_info_response->getStatusCode()
            );
        }
    }
}
