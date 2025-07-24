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

namespace Tuleap\OAuth2ServerCore\AuthorizationServer;

use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\OAuth2ServerCore\App\OAuth2App;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\OAuth2AuthorizationCodeCreator;
use Tuleap\Test\Builders\UserTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AuthorizationCodeResponseFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /** @var AuthorizationCodeResponseFactory */
    private $authorization_code_response_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&OAuth2AuthorizationCodeCreator
     */
    private $authorization_code_creator;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\URLRedirect
     */
    private $url_redirect;

    #[\Override]
    protected function setUp(): void
    {
        $this->authorization_code_creator          = $this->createMock(OAuth2AuthorizationCodeCreator::class);
        $this->url_redirect                        = $this->createMock(\URLRedirect::class);
        $this->authorization_code_response_factory = new AuthorizationCodeResponseFactory(
            HTTPFactoryBuilder::responseFactory(),
            $this->authorization_code_creator,
            new RedirectURIBuilder(HTTPFactoryBuilder::URIFactory()),
            $this->url_redirect,
            HTTPFactoryBuilder::URIFactory()
        );
    }

    public function testCreateSuccessfulResponseRedirectsToRedirectURIWithAuthorizationCode(): void
    {
        $auth_code = 'auth_code_identifier';
        $this->authorization_code_creator->expects($this->once())->method('createAuthorizationCodeIdentifier')->willReturn(
            new ConcealedString($auth_code)
        );

        $response = $this->authorization_code_response_factory->createSuccessfulResponse(
            $this->buildOAuth2App(),
            [$this->createMock(AuthenticationScope::class)],
            UserTestBuilder::aUser()->withId(102)->build(),
            'https://example.com',
            null,
            null,
            null
        );
        self::assertSame(302, $response->getStatusCode());
        $location = $response->getHeaderLine('Location');
        $this->assertStringContainsString('https://example.com', $location);
        $this->assertStringContainsString('code=' . $auth_code, $location);
    }

    public function testCreateSuccessfulResponseRedirectsWithStateWhenNotNull(): void
    {
        $auth_code = 'auth_code_identifier';
        $this->authorization_code_creator->expects($this->once())->method('createAuthorizationCodeIdentifier')->willReturn(
            new ConcealedString($auth_code)
        );

        $response = $this->authorization_code_response_factory->createSuccessfulResponse(
            $this->buildOAuth2App(),
            [$this->createMock(AuthenticationScope::class)],
            UserTestBuilder::aUser()->withId(102)->build(),
            'https://example.com',
            '6k9Sfw',
            'pkce_code_challenge',
            'oidc_nonce'
        );
        self::assertSame(302, $response->getStatusCode());
        $location = $response->getHeaderLine('Location');
        $this->assertStringContainsString('https://example.com', $location);
        $this->assertStringContainsString('state=6k9Sfw', $location);
        $this->assertStringContainsString('code=' . $auth_code, $location);
    }

    public function testCreateErrorResponseRedirectsWithErrorCode(): void
    {
        $response = $this->authorization_code_response_factory->createErrorResponse(
            AuthorizationEndpointController::ERROR_CODE_INVALID_REQUEST,
            'https://example.com',
            null
        );
        self::assertSame(302, $response->getStatusCode());
        $location = $response->getHeaderLine('Location');
        $this->assertStringContainsString('https://example.com', $location);
        $this->assertStringContainsString('error=invalid_request', $location);
    }

    public function testCreateErrorResponseRedirectsWithStateWhenNotNull(): void
    {
        $response = $this->authorization_code_response_factory->createErrorResponse(
            AuthorizationEndpointController::ERROR_CODE_INVALID_REQUEST,
            'https://example.com',
            '9EEbiaQfNRQXusHSe'
        );
        self::assertSame(302, $response->getStatusCode());
        $location = $response->getHeaderLine('Location');
        $this->assertStringContainsString('https://example.com', $location);
        $this->assertStringContainsString('state=9EEbiaQfNRQXusHSe', $location);
        $this->assertStringContainsString('error=invalid_request', $location);
    }

    public function testCreateRedirectToLoginResponse(): void
    {
        $this->url_redirect->expects($this->once())->method('buildReturnToLogin')
            ->with(['REQUEST_URI' => '/oauth2/authorize?client_id=1'])->willReturn('/login');
        $request  = new NullServerRequest();
        $request  = $request->withUri($request->getUri()->withHost('example.com')->withPath('/oauth2/authorize'));
        $response = $this->authorization_code_response_factory->createRedirectToLoginResponse($request, ['client_id' => '1']);
        self::assertSame(302, $response->getStatusCode());
        $this->assertEquals('/login?&prompt=login', $response->getHeaderLine('Location'));
    }

    public function testCreateRedirectToLoginResponseAndLoginValueFromPromptToAvoidAnInfiniteRedirectionLoop(): void
    {
        $this->url_redirect->expects($this->once())->method('buildReturnToLogin')
            ->with(['REQUEST_URI' => '/oauth2/authorize?client_id=1&prompt=consent'])->willReturn('/login');
        $request  = new NullServerRequest();
        $request  = $request->withUri($request->getUri()->withHost('example.com')->withPath('/oauth2/authorize'));
        $response = $this->authorization_code_response_factory->createRedirectToLoginResponse($request, ['client_id' => '1', 'prompt' => 'login consent']);
        self::assertSame(302, $response->getStatusCode());
        $this->assertEquals('/login?&prompt=login', $response->getHeaderLine('Location'));
    }

    private function buildOAuth2App(): OAuth2App
    {
        return new OAuth2App(1, 'Name', 'https://example.com/redirect', true, new \Project(['group_id' => 102]));
    }
}
