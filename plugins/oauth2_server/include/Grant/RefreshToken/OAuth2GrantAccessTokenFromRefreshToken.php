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

namespace Tuleap\OAuth2Server\Grant\RefreshToken;

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
use Tuleap\OAuth2Server\OAuth2ServerException;
use Tuleap\OAuth2Server\RefreshToken\OAuth2RefreshToken;
use Tuleap\OAuth2Server\RefreshToken\OAuth2RefreshTokenVerifier;
use Tuleap\OAuth2Server\RefreshToken\OAuth2ScopeNotCoveredByOneOfTheScopeAssociatedWithTheRefreshTokenException;
use Tuleap\OAuth2Server\Scope\InvalidOAuth2ScopeException;
use Tuleap\OAuth2Server\Scope\ScopeExtractor;

class OAuth2GrantAccessTokenFromRefreshToken
{
    // See https://tools.ietf.org/html/rfc6749#section-6
    private const REFRESH_TOKEN_PARAMETER = 'refresh_token';
    private const SCOPE_PARAMETER         = 'scope';

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
     * @var SplitTokenIdentifierTranslator
     */
    private $access_token_identifier_unserializer;
    /**
     * @var OAuth2RefreshTokenVerifier
     */
    private $refresh_token_verifier;
    /**
     * @var AccessTokenGrantRepresentationBuilder
     */
    private $access_token_grant_representation_builder;
    /**
     * @var ScopeExtractor
     */
    private $scope_extractor;

    public function __construct(
        ResponseFactoryInterface $response_factory,
        StreamFactoryInterface $stream_factory,
        AccessTokenGrantErrorResponseBuilder $access_token_grant_error_response_builder,
        SplitTokenIdentifierTranslator $refresh_token_identifier_unserializer,
        OAuth2RefreshTokenVerifier $refresh_token_verifier,
        AccessTokenGrantRepresentationBuilder $access_token_grant_representation_builder,
        ScopeExtractor $scope_extractor
    ) {
        $this->response_factory                          = $response_factory;
        $this->stream_factory                            = $stream_factory;
        $this->access_token_grant_error_response_builder = $access_token_grant_error_response_builder;
        $this->access_token_identifier_unserializer      = $refresh_token_identifier_unserializer;
        $this->refresh_token_verifier                    = $refresh_token_verifier;
        $this->access_token_grant_representation_builder = $access_token_grant_representation_builder;
        $this->scope_extractor                           = $scope_extractor;
    }

    public function grantAccessToken(OAuth2App $app, array $body_params): ResponseInterface
    {
        if (! isset($body_params[self::REFRESH_TOKEN_PARAMETER])) {
            return $this->access_token_grant_error_response_builder->buildInvalidRequestResponse();
        }

        try {
            $refresh_token = $this->refresh_token_verifier->getRefreshToken(
                $app,
                $this->access_token_identifier_unserializer->getSplitToken(new ConcealedString($body_params[self::REFRESH_TOKEN_PARAMETER]))
            );
        } catch (OAuth2ServerException | SplitTokenException $exception) {
            return $this->access_token_grant_error_response_builder->buildInvalidGrantResponse();
        } finally {
            \sodium_memzero($body_params[self::REFRESH_TOKEN_PARAMETER]);
        }

        if (isset($body_params[self::SCOPE_PARAMETER])) {
            try {
                $refresh_token = OAuth2RefreshToken::createWithAReducedSetOfScopes(
                    $refresh_token,
                    $this->scope_extractor->extractScopes((string) $body_params[self::SCOPE_PARAMETER])
                );
            } catch (OAuth2ScopeNotCoveredByOneOfTheScopeAssociatedWithTheRefreshTokenException | InvalidOAuth2ScopeException $exception) {
                return $this->access_token_grant_error_response_builder->buildInvalidScopeResponse();
            }
        }

        $representation = $this->access_token_grant_representation_builder->buildRepresentationFromRefreshToken(
            new \DateTimeImmutable(),
            $refresh_token
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
