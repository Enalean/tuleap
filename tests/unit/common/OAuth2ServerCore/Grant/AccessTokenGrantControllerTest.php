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

namespace Tuleap\OAuth2ServerCore\Grant;

use PHPUnit\Framework\MockObject\Stub;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\NullLogger;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\OAuth2ServerCore\App\OAuth2App;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\OAuth2GrantAccessTokenFromAuthorizationCode;
use Tuleap\OAuth2ServerCore\Grant\RefreshToken\OAuth2GrantAccessTokenFromRefreshToken;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Helpers\NoopSapiEmitter;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AccessTokenGrantControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private AccessTokenGrantController $controller;
    private \Psr\Http\Message\ResponseFactoryInterface $response_factory;
    private OAuth2GrantAccessTokenFromAuthorizationCode&Stub $grant_access_token_from_auth_code;
    private OAuth2GrantAccessTokenFromRefreshToken&Stub $grant_access_token_from_refresh_token;

    #[\Override]
    protected function setUp(): void
    {
        $this->grant_access_token_from_auth_code     = $this->createStub(OAuth2GrantAccessTokenFromAuthorizationCode::class);
        $this->grant_access_token_from_refresh_token = $this->createStub(OAuth2GrantAccessTokenFromRefreshToken::class);

        $this->response_factory = HTTPFactoryBuilder::responseFactory();
        $stream_factory         = HTTPFactoryBuilder::streamFactory();

        $this->controller = new AccessTokenGrantController(
            new AccessTokenGrantErrorResponseBuilder($this->response_factory, $stream_factory),
            $this->grant_access_token_from_auth_code,
            $this->grant_access_token_from_refresh_token,
            new NullLogger(),
            new NoopSapiEmitter(),
        );
    }

    public function testSuccessfullyGrantAccessTokenWithAnAuthorizationCode(): void
    {
        $expected_response = $this->response_factory->createResponse();

        $this->grant_access_token_from_auth_code->method('grantAccessToken')->willReturn($expected_response);

        $request = $this->createStub(ServerRequestInterface::class);
        $app     = $this->buildOAuth2App();
        $request->method('getAttribute')->with(OAuth2ClientAuthenticationMiddleware::class)->willReturn($app);
        $request->method('getParsedBody')->willReturn(
            ['grant_type' => 'authorization_code']
        );

        $response = $this->controller->handle($request);
        self::assertSame($expected_response, $response);
    }

    public function testSuccessfullyGrantAccessTokenWithRefreshToken(): void
    {
        $expected_response = $this->response_factory->createResponse();

        $this->grant_access_token_from_refresh_token->method('grantAccessToken')->willReturn($expected_response);

        $request = $this->createStub(ServerRequestInterface::class);
        $app     = $this->buildOAuth2App();
        $request->method('getAttribute')->with(OAuth2ClientAuthenticationMiddleware::class)->willReturn($app);
        $request->method('getParsedBody')->willReturn(
            ['grant_type' => 'refresh_token']
        );

        $response = $this->controller->handle($request);
        self::assertSame($expected_response, $response);
    }

    public function testRejectsRequestThatDoesNotHaveAnExplicitGrantType(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getAttribute')->with(OAuth2ClientAuthenticationMiddleware::class)->willReturn($this->buildOAuth2App());
        $request->method('getParsedBody')->willReturn(null);

        $response = $this->controller->handle($request);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/json;charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertJsonStringEqualsJsonString('{"error":"invalid_request"}', $response->getBody()->getContents());
    }

    public function testRejectsRequestWithAnUnsupportedGrantType(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getAttribute')->with(OAuth2ClientAuthenticationMiddleware::class)->willReturn($this->buildOAuth2App());
        $request->method('getParsedBody')->willReturn(['grant_type' => 'password']);

        $response = $this->controller->handle($request);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/json;charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertJsonStringEqualsJsonString('{"error":"invalid_grant"}', $response->getBody()->getContents());
    }

    public function testRejectsRequestWhereTheClientHasNotBeenAuthenticated(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getAttribute')->with(OAuth2ClientAuthenticationMiddleware::class)->willReturn(null);

        $response = $this->controller->handle($request);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('WWW-Authenticate'));
        $this->assertEquals('application/json;charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertJsonStringEqualsJsonString('{"error":"invalid_client"}', $response->getBody()->getContents());
    }

    private function buildOAuth2App(): OAuth2App
    {
        return new OAuth2App(1, 'name', 'https://example.com', true, ProjectTestBuilder::aProject()->build());
    }
}
