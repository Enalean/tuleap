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

namespace Tuleap\OAuth2Server\Grant\AuthorizationCode;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Tuleap\Authentication\SplitToken\SplitTokenException;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\OAuth2Server\App\OAuth2App;
use Tuleap\OAuth2Server\Grant\AccessTokenGrantController;
use Tuleap\OAuth2Server\Grant\AccessTokenGrantErrorResponseBuilder;
use Tuleap\OAuth2Server\Grant\AccessTokenGrantRepresentationBuilder;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\PKCE\OAuth2PKCEVerificationException;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\PKCE\PKCECodeVerifier;
use Tuleap\OAuth2Server\OAuth2ServerException;

class OAuth2GrantAccessTokenFromAuthorizationCode
{
    private const AUTH_CODE_PARAMETER     = 'code';
    private const REDIRECT_URI_PARAMETER  = 'redirect_uri';
    private const CODE_VERIFIER_PARAMETER = 'code_verifier';
    /**
     * @var ResponseFactoryInterface
     */
    private $response_factory;
    /**
     * @var StreamFactoryInterface
     */
    private $stream_factory;
    /**
     * @var AccessTokenGrantErrorResponseBuilder
     */
    private $access_token_grant_error_response_builder;
    /**
     * @var AccessTokenGrantRepresentationBuilder
     */
    private $response_builder;
    /**
     * @var SplitTokenIdentifierTranslator
     */
    private $access_token_identifier_unserializer;
    /**
     * @var OAuth2AuthorizationCodeVerifier
     */
    private $authorization_code_verifier;
    /**
     * @var PKCECodeVerifier
     */
    private $pkce_code_verifier;

    public function __construct(
        ResponseFactoryInterface $response_factory,
        StreamFactoryInterface $stream_factory,
        AccessTokenGrantErrorResponseBuilder $access_token_grant_error_response_builder,
        AccessTokenGrantRepresentationBuilder $representation_builder,
        SplitTokenIdentifierTranslator $access_token_identifier_unserializer,
        OAuth2AuthorizationCodeVerifier $authorization_code_verifier,
        PKCECodeVerifier $pkce_code_verifier
    ) {
        $this->response_factory = $response_factory;
        $this->stream_factory = $stream_factory;
        $this->access_token_grant_error_response_builder = $access_token_grant_error_response_builder;
        $this->response_builder = $representation_builder;
        $this->access_token_identifier_unserializer = $access_token_identifier_unserializer;
        $this->authorization_code_verifier = $authorization_code_verifier;
        $this->pkce_code_verifier = $pkce_code_verifier;
    }

    public function grantAccessToken(OAuth2App $app, array $body_params): ResponseInterface
    {
        if (! isset($body_params[self::AUTH_CODE_PARAMETER])) {
            return $this->access_token_grant_error_response_builder->buildInvalidRequestResponse();
        }

        try {
            $authorization_code = $this->authorization_code_verifier->getAuthorizationCode(
                $this->access_token_identifier_unserializer->getSplitToken(new ConcealedString($body_params[self::AUTH_CODE_PARAMETER]))
            );
        } catch (OAuth2ServerException | SplitTokenException $exception) {
            return $this->access_token_grant_error_response_builder->buildInvalidGrantResponse();
        } finally {
            \sodium_memzero($body_params[self::AUTH_CODE_PARAMETER]);
        }

        try {
            $this->pkce_code_verifier->verifyCode($authorization_code, $body_params[self::CODE_VERIFIER_PARAMETER] ?? null);
        } catch (OAuth2PKCEVerificationException $exception) {
            return $this->access_token_grant_error_response_builder->buildInvalidGrantResponse();
        }

        if (! isset($body_params[self::REDIRECT_URI_PARAMETER])) {
            return $this->access_token_grant_error_response_builder->buildInvalidRequestResponse();
        }
        if (! \hash_equals($app->getRedirectEndpoint(), $body_params[self::REDIRECT_URI_PARAMETER])) {
            return $this->access_token_grant_error_response_builder->buildInvalidGrantResponse();
        }

        $representation = $this->response_builder->buildRepresentationFromAuthorizationCode(
            new \DateTimeImmutable(),
            $app,
            $authorization_code
        );

        return $this->response_factory->createResponse()
            ->withHeader('Content-Type', AccessTokenGrantController::CONTENT_TYPE_RESPONSE)
            ->withBody(
                $this->stream_factory->createStream(
                    json_encode($representation, JSON_THROW_ON_ERROR)
                )
            );
    }
}
