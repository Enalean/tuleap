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

namespace Tuleap\OAuth2ServerCore\Grant\RefreshToken;

use Psr\Log\NullLogger;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenException;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\OAuth2ServerCore\AccessToken\OAuth2AccessTokenWithIdentifier;
use Tuleap\OAuth2ServerCore\App\OAuth2App;
use Tuleap\OAuth2ServerCore\Grant\AccessTokenGrantErrorResponseBuilder;
use Tuleap\OAuth2ServerCore\Grant\AccessTokenGrantRepresentationBuilder;
use Tuleap\OAuth2ServerCore\Grant\OAuth2AccessTokenSuccessfulRequestRepresentation;
use Tuleap\OAuth2ServerCore\OAuth2ServerException;
use Tuleap\OAuth2ServerCore\OAuth2TestScope;
use Tuleap\OAuth2ServerCore\RefreshToken\OAuth2RefreshToken;
use Tuleap\OAuth2ServerCore\RefreshToken\OAuth2RefreshTokenVerifier;
use Tuleap\OAuth2ServerCore\Scope\InvalidOAuth2ScopeException;
use Tuleap\OAuth2ServerCore\Scope\ScopeExtractor;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OAuth2GrantAccessTokenFromRefreshTokenTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AccessTokenGrantRepresentationBuilder
     */
    private $representation_builder;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&SplitTokenIdentifierTranslator
     */
    private $refresh_token_unserializer;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&OAuth2RefreshTokenVerifier
     */
    private $refresh_token_verifier;
    /**
     * @var OAuth2GrantAccessTokenFromRefreshToken
     */
    private $grant_access_from_refresh_token;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ScopeExtractor
     */
    private $scope_extractor;

    protected function setUp(): void
    {
        $this->representation_builder     = $this->createMock(AccessTokenGrantRepresentationBuilder::class);
        $this->refresh_token_unserializer = $this->createMock(SplitTokenIdentifierTranslator::class);
        $this->refresh_token_verifier     = $this->createMock(OAuth2RefreshTokenVerifier::class);
        $this->scope_extractor            = $this->createMock(ScopeExtractor::class);

        $response_factory = HTTPFactoryBuilder::responseFactory();
        $stream_factory   = HTTPFactoryBuilder::streamFactory();

        $this->grant_access_from_refresh_token = new OAuth2GrantAccessTokenFromRefreshToken(
            $response_factory,
            $stream_factory,
            new AccessTokenGrantErrorResponseBuilder($response_factory, $stream_factory),
            $this->refresh_token_unserializer,
            $this->refresh_token_verifier,
            $this->representation_builder,
            $this->scope_extractor,
            new NullLogger()
        );
    }

    public function testBuildsSuccessfulResponse(): void
    {
        $this->refresh_token_unserializer->method('getSplitToken')->willReturn($this->createMock(SplitToken::class));
        $this->refresh_token_verifier->method('getRefreshToken')->willReturn(
            $this->buildRefreshToken()
        );
        $this->representation_builder->method('buildRepresentationFromRefreshToken')->willReturn(
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
        $this->refresh_token_unserializer->method('getSplitToken')->willReturn($this->createMock(SplitToken::class));
        $this->refresh_token_verifier->method('getRefreshToken')->willReturn(
            $this->buildRefreshToken()
        );
        $this->representation_builder->method('buildRepresentationFromRefreshToken')->willReturn(
            OAuth2AccessTokenSuccessfulRequestRepresentation::fromAccessTokenAndRefreshToken(
                new OAuth2AccessTokenWithIdentifier(new ConcealedString('identifier'), new \DateTimeImmutable('@20')),
                null,
                new \DateTimeImmutable('@10')
            )
        );
        $this->scope_extractor->method('extractScopes')->willReturn([OAuth2TestScope::fromItself()]);

        $body_params = ['refresh_token' => 'valid_refresh_token', 'scope' => 'already_covered'];

        $response = $this->grant_access_from_refresh_token->grantAccessToken($this->buildOAuth2App(), $body_params);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json;charset=UTF-8', $response->getHeaderLine('Content-Type'));
    }

    public function testRejectsRequestWithoutARefreshToken(): void
    {
        $body_params = [];

        $this->representation_builder->expects(self::never())->method('buildRepresentationFromRefreshToken');
        $response = $this->grant_access_from_refresh_token->grantAccessToken($this->buildOAuth2App(), $body_params);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/json;charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertJsonStringEqualsJsonString('{"error":"invalid_request"}', $response->getBody()->getContents());
    }

    public function testRejectsWithANotValidRefreshToken(): void
    {
        $this->refresh_token_unserializer->method('getSplitToken')->willReturn($this->createMock(SplitToken::class));
        $this->refresh_token_verifier->method('getRefreshToken')->willThrowException(
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
        $this->refresh_token_unserializer->method('getSplitToken')->willThrowException(
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
        $this->refresh_token_unserializer->method('getSplitToken')->willReturn($this->createMock(SplitToken::class));
        $this->refresh_token_verifier->method('getRefreshToken')->willReturn(
            $this->buildRefreshToken()
        );
        $this->representation_builder->method('buildRepresentationFromRefreshToken')->willReturn(
            OAuth2AccessTokenSuccessfulRequestRepresentation::fromAccessTokenAndRefreshToken(
                new OAuth2AccessTokenWithIdentifier(new ConcealedString('identifier'), new \DateTimeImmutable('@20')),
                null,
                new \DateTimeImmutable('@10')
            )
        );
        $this->scope_extractor->method('extractScopes')->willThrowException(
            InvalidOAuth2ScopeException::scopeDoesNotExist(OAuth2ScopeIdentifier::fromIdentifierKey('donotexist'))
        );

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
