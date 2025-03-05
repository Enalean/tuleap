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

namespace Tuleap\OAuth2ServerCore\Grant\AuthorizationCode;

use Psr\Log\NullLogger;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\OAuth2ServerCore\AccessToken\OAuth2AccessTokenWithIdentifier;
use Tuleap\OAuth2ServerCore\App\OAuth2App;
use Tuleap\OAuth2ServerCore\Grant\AccessTokenGrantErrorResponseBuilder;
use Tuleap\OAuth2ServerCore\Grant\AccessTokenGrantRepresentationBuilder;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\PKCE\OAuth2PKCEVerificationException;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\PKCE\PKCECodeVerifier;
use Tuleap\OAuth2ServerCore\Grant\OAuth2AccessTokenSuccessfulRequestRepresentation;
use Tuleap\OAuth2ServerCore\OAuth2ServerException;
use Tuleap\OAuth2ServerCore\OAuth2TestScope;
use Tuleap\Test\Builders\ProjectTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OAuth2GrantAccessTokenFromAuthorizationCodeTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AccessTokenGrantRepresentationBuilder
     */
    private $representation_builder;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&SplitTokenIdentifierTranslator
     */
    private $auth_code_unserializer;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&OAuth2AuthorizationCodeVerifier
     */
    private $auth_code_verifier;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PKCECodeVerifier
     */
    private $pkce_code_verifier;
    /**
     * @var OAuth2GrantAccessTokenFromAuthorizationCode
     */
    private $grant_access_token_from_auth_code;

    protected function setUp(): void
    {
        $this->representation_builder = $this->createMock(AccessTokenGrantRepresentationBuilder::class);
        $this->auth_code_unserializer = $this->createMock(SplitTokenIdentifierTranslator::class);
        $this->auth_code_verifier     = $this->createMock(OAuth2AuthorizationCodeVerifier::class);
        $this->pkce_code_verifier     = $this->createMock(PKCECodeVerifier::class);

        $response_factory = HTTPFactoryBuilder::responseFactory();
        $stream_factory   = HTTPFactoryBuilder::streamFactory();

        $this->grant_access_token_from_auth_code = new OAuth2GrantAccessTokenFromAuthorizationCode(
            $response_factory,
            $stream_factory,
            new AccessTokenGrantErrorResponseBuilder($response_factory, $stream_factory),
            $this->representation_builder,
            $this->auth_code_unserializer,
            $this->auth_code_verifier,
            $this->pkce_code_verifier,
            new NullLogger()
        );
    }

    public function testBuildsSuccessfulResponse(): void
    {
        $this->auth_code_unserializer->method('getSplitToken')->willReturn($this->createMock(SplitToken::class));
        $this->auth_code_verifier->method('getAuthorizationCode')->willReturn(
            $this->buildAuthorizationCodeGrant()
        );
        $this->pkce_code_verifier->expects(self::once())->method('verifyCode');
        $this->representation_builder->method('buildRepresentationFromAuthorizationCode')->willReturn(
            OAuth2AccessTokenSuccessfulRequestRepresentation::fromAccessTokenAndRefreshToken(
                new OAuth2AccessTokenWithIdentifier(new ConcealedString('identifier'), new \DateTimeImmutable('@20')),
                null,
                new \DateTimeImmutable('@10')
            )
        );

        $app         = $this->buildOAuth2App();
        $body_params = [
            'grant_type'   => 'authorization_code',
            'code'         => 'tlp-oauth2-ac1-1.6161616161616161616161616161616161616161616161616161616161616161',
            'redirect_uri' => $app->getRedirectEndpoint(),
        ];

        $response = $this->grant_access_token_from_auth_code->grantAccessToken($this->buildOAuth2App(), $body_params);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json;charset=UTF-8', $response->getHeaderLine('Content-Type'));
    }

    public function testRejectsRequestWithoutAnAuthCode(): void
    {
        $this->auth_code_unserializer->method('getSplitToken')->willReturn($this->createMock(SplitToken::class));
        $this->auth_code_verifier->method('getAuthorizationCode')->willReturn(
            $this->buildAuthorizationCodeGrant()
        );

        $body_params = ['grant_type' => 'authorization_code'];

        $this->representation_builder->expects(self::never())->method('buildRepresentationFromAuthorizationCode');
        $response = $this->grant_access_token_from_auth_code->grantAccessToken($this->buildOAuth2App(), $body_params);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/json;charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertJsonStringEqualsJsonString('{"error":"invalid_request"}', $response->getBody()->getContents());
    }

    public function testRejectsWithANotValidAuthCode(): void
    {
        $this->auth_code_unserializer->method('getSplitToken')->willReturn($this->createMock(SplitToken::class));
        $this->auth_code_verifier->method('getAuthorizationCode')->willThrowException(
            new class extends \RuntimeException implements OAuth2ServerException {
            }
        );

        $body_params = ['grant_type' => 'authorization_code', 'code' => 'not_valid_auth_code'];

        $this->representation_builder->expects(self::never())->method('buildRepresentationFromAuthorizationCode');
        $response = $this->grant_access_token_from_auth_code->grantAccessToken($this->buildOAuth2App(), $body_params);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/json;charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertJsonStringEqualsJsonString('{"error":"invalid_grant"}', $response->getBody()->getContents());
    }

    public function testRejectsRequestWithoutARedirectURI(): void
    {
        $this->auth_code_unserializer->method('getSplitToken')->willReturn($this->createMock(SplitToken::class));
        $this->auth_code_verifier->method('getAuthorizationCode')->willReturn(
            $this->buildAuthorizationCodeGrant()
        );
        $this->pkce_code_verifier->method('verifyCode');

        $body_params = [
            'grant_type' => 'authorization_code',
            'code'       => 'tlp-oauth2-ac1-1.6161616161616161616161616161616161616161616161616161616161616161',
        ];

        $this->representation_builder->expects(self::never())->method('buildRepresentationFromAuthorizationCode');
        $response = $this->grant_access_token_from_auth_code->grantAccessToken($this->buildOAuth2App(), $body_params);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/json;charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertJsonStringEqualsJsonString('{"error":"invalid_request"}', $response->getBody()->getContents());
    }

    public function testRejectsRequestThatDoesNotTheExpectedRedirectURI(): void
    {
        $this->auth_code_unserializer->method('getSplitToken')->willReturn($this->createMock(SplitToken::class));
        $this->auth_code_verifier->method('getAuthorizationCode')->willReturn(
            $this->buildAuthorizationCodeGrant()
        );
        $this->pkce_code_verifier->method('verifyCode');

        $body_params = [
            'grant_type'   => 'authorization_code',
            'code'         => 'tlp-oauth2-ac1-1.6161616161616161616161616161616161616161616161616161616161616161',
            'redirect_uri' => 'https://evil.example.com',
        ];

        $this->representation_builder->expects(self::never())->method('buildRepresentationFromAuthorizationCode');
        $response = $this->grant_access_token_from_auth_code->grantAccessToken($this->buildOAuth2App(), $body_params);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/json;charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertJsonStringEqualsJsonString('{"error":"invalid_grant"}', $response->getBody()->getContents());
    }

    public function testRejectsRequestWithAInvalidPKCECodeVerifier(): void
    {
        $this->auth_code_unserializer->method('getSplitToken')->willReturn($this->createMock(SplitToken::class));
        $this->auth_code_verifier->method('getAuthorizationCode')->willReturn(
            $this->buildAuthorizationCodeGrant()
        );
        $this->pkce_code_verifier->method('verifyCode')->willThrowException(
            new class extends \RuntimeException implements OAuth2PKCEVerificationException
            {
            }
        );

        $app         = $this->buildOAuth2App();
        $body_params = [
            'grant_type'   => 'authorization_code',
            'code'         => 'tlp-oauth2-ac1-1.6161616161616161616161616161616161616161616161616161616161616161',
            'redirect_uri' => $app->getRedirectEndpoint(),
        ];

        $this->representation_builder->expects(self::never())->method('buildRepresentationFromAuthorizationCode');
        $response = $this->grant_access_token_from_auth_code->grantAccessToken($app, $body_params);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/json;charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertJsonStringEqualsJsonString('{"error":"invalid_grant"}', $response->getBody()->getContents());
    }

    private function buildOAuth2App(): OAuth2App
    {
        return new OAuth2App(1, 'name', 'https://example.com', true, ProjectTestBuilder::aProject()->build());
    }

    private function buildAuthorizationCodeGrant(): OAuth2AuthorizationCode
    {
        return OAuth2AuthorizationCode::approveForSetOfScopes(
            new SplitToken(1, SplitTokenVerificationString::generateNewSplitTokenVerificationString()),
            new \PFUser(['language_id' => 'en']),
            'pkce_code_challenge',
            'oidc_nonce',
            [OAuth2TestScope::fromItself()],
        );
    }
}
