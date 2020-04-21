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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenException;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\OAuth2Server\AccessToken\OAuth2AccessTokenWithIdentifier;
use Tuleap\OAuth2Server\App\OAuth2App;
use Tuleap\OAuth2Server\Grant\AccessTokenGrantErrorResponseBuilder;
use Tuleap\OAuth2Server\Grant\AccessTokenGrantRepresentationBuilder;
use Tuleap\OAuth2Server\Grant\OAuth2AccessTokenSuccessfulRequestRepresentation;
use Tuleap\OAuth2Server\OAuth2ServerException;
use Tuleap\OAuth2Server\OAuth2TestScope;
use Tuleap\OAuth2Server\RefreshToken\OAuth2RefreshToken;
use Tuleap\OAuth2Server\RefreshToken\OAuth2RefreshTokenVerifier;
use Tuleap\OAuth2Server\Scope\InvalidOAuth2ScopeException;
use Tuleap\OAuth2Server\Scope\ScopeExtractor;

final class OAuth2GrantAccessTokenFromRefreshTokenTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|AccessTokenGrantRepresentationBuilder
     */
    private $representation_builder;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|SplitTokenIdentifierTranslator
     */
    private $refresh_token_unserializer;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|OAuth2RefreshTokenVerifier
     */
    private $refresh_token_verifier;
    /**
     * @var OAuth2GrantAccessTokenFromRefreshToken
     */
    private $grant_access_from_refresh_token;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ScopeExtractor
     */
    private $scope_extractor;

    protected function setUp(): void
    {
        $this->representation_builder     = \Mockery::mock(AccessTokenGrantRepresentationBuilder::class);
        $this->refresh_token_unserializer = \Mockery::mock(SplitTokenIdentifierTranslator::class);
        $this->refresh_token_verifier     = \Mockery::mock(OAuth2RefreshTokenVerifier::class);
        $this->scope_extractor            = \Mockery::mock(ScopeExtractor::class);

        $response_factory = HTTPFactoryBuilder::responseFactory();
        $stream_factory   = HTTPFactoryBuilder::streamFactory();

        $this->grant_access_from_refresh_token = new OAuth2GrantAccessTokenFromRefreshToken(
            $response_factory,
            $stream_factory,
            new AccessTokenGrantErrorResponseBuilder($response_factory, $stream_factory),
            $this->refresh_token_unserializer,
            $this->refresh_token_verifier,
            $this->representation_builder,
            $this->scope_extractor
        );
    }

    public function testBuildsSuccessfulResponse(): void
    {
        $this->refresh_token_unserializer->shouldReceive('getSplitToken')->andReturn(\Mockery::mock(SplitToken::class));
        $this->refresh_token_verifier->shouldReceive('getRefreshToken')->andReturn(
            $this->buildRefreshToken()
        );
        $this->representation_builder->shouldReceive('buildRepresentationFromRefreshToken')->andReturn(
            OAuth2AccessTokenSuccessfulRequestRepresentation::fromAccessTokenAndRefreshToken(
                new OAuth2AccessTokenWithIdentifier(new ConcealedString('identifier'), new \DateTimeImmutable('@20')),
                null,
                new \DateTimeImmutable('@10')
            )
        );

        $body_params = ['refresh_token' => 'valid_refresh_token',];

        $response = $this->grant_access_from_refresh_token->grantAccessToken($this->buildOAuth2App(), $body_params);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json;charset=UTF-8', $response->getHeaderLine('Content-Type'));
    }

    public function testBuildsSuccessfulResponseWithLessScopes(): void
    {
        $this->refresh_token_unserializer->shouldReceive('getSplitToken')->andReturn(\Mockery::mock(SplitToken::class));
        $this->refresh_token_verifier->shouldReceive('getRefreshToken')->andReturn(
            $this->buildRefreshToken()
        );
        $this->representation_builder->shouldReceive('buildRepresentationFromRefreshToken')->andReturn(
            OAuth2AccessTokenSuccessfulRequestRepresentation::fromAccessTokenAndRefreshToken(
                new OAuth2AccessTokenWithIdentifier(new ConcealedString('identifier'), new \DateTimeImmutable('@20')),
                null,
                new \DateTimeImmutable('@10')
            )
        );
        $this->scope_extractor->shouldReceive('extractScopes')->andReturn([OAuth2TestScope::fromItself()]);

        $body_params = ['refresh_token' => 'valid_refresh_token', 'scope' => 'already_covered'];

        $response = $this->grant_access_from_refresh_token->grantAccessToken($this->buildOAuth2App(), $body_params);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json;charset=UTF-8', $response->getHeaderLine('Content-Type'));
    }

    public function testRejectsRequestWithoutARefreshToken(): void
    {
        $body_params = [];

        $this->representation_builder->shouldNotReceive('buildResponse');
        $response = $this->grant_access_from_refresh_token->grantAccessToken($this->buildOAuth2App(), $body_params);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/json;charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertJsonStringEqualsJsonString('{"error":"invalid_request"}', $response->getBody()->getContents());
    }

    public function testRejectsWithANotValidRefreshToken(): void
    {
        $this->refresh_token_unserializer->shouldReceive('getSplitToken')->andReturn(\Mockery::mock(SplitToken::class));
        $this->refresh_token_verifier->shouldReceive('getRefreshToken')->andThrow(
            new class extends \RuntimeException implements OAuth2ServerException {
            }
        );

        $body_params = ['refresh_token' => 'not_valid_refresh_token',];

        $response = $this->grant_access_from_refresh_token->grantAccessToken($this->buildOAuth2App(), $body_params);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"error":"invalid_grant"}', $response->getBody()->getContents());
    }

    public function testRejectsWithARefreshTokenThatCannotBeUnserialized(): void
    {
        $this->refresh_token_unserializer->shouldReceive('getSplitToken')->andThrow(
            new class extends SplitTokenException {
            }
        );

        $body_params = ['refresh_token' => 'does_not_even_look_like_a_refresh_token',];

        $response = $this->grant_access_from_refresh_token->grantAccessToken($this->buildOAuth2App(), $body_params);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"error":"invalid_grant"}', $response->getBody()->getContents());
    }

    public function testRejectsWhenScopeCannotBeProperlyExtracted(): void
    {
        $this->refresh_token_unserializer->shouldReceive('getSplitToken')->andReturn(\Mockery::mock(SplitToken::class));
        $this->refresh_token_verifier->shouldReceive('getRefreshToken')->andReturn(
            $this->buildRefreshToken()
        );
        $this->representation_builder->shouldReceive('buildRepresentationFromRefreshToken')->andReturn(
            OAuth2AccessTokenSuccessfulRequestRepresentation::fromAccessTokenAndRefreshToken(
                new OAuth2AccessTokenWithIdentifier(new ConcealedString('identifier'), new \DateTimeImmutable('@20')),
                null,
                new \DateTimeImmutable('@10')
            )
        );
        $this->scope_extractor->shouldReceive('extractScopes')->andThrow(new InvalidOAuth2ScopeException());

        $body_params = ['refresh_token' => 'valid_refresh_token', 'scope' => ''];

        $response = $this->grant_access_from_refresh_token->grantAccessToken($this->buildOAuth2App(), $body_params);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"error":"invalid_scope"}', $response->getBody()->getContents());
    }

    private function buildOAuth2App(): OAuth2App
    {
        return new OAuth2App(
            14,
            'Refresh token grant',
            'https://example.com',
            true,
            new \Project(['group_id' => 101])
        );
    }

    private function buildRefreshToken(): OAuth2RefreshToken
    {
        return OAuth2RefreshToken::createWithASetOfScopes(
            1,
            [OAuth2TestScope::fromItself()],
        );
    }
}
