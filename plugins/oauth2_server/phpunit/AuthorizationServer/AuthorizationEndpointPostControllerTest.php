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

namespace Tuleap\OAuth2Server\AuthorizationServer;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\UserTestBuilder;

final class AuthorizationEndpointPostControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var AuthorizationEndpointPostController
     */
    private $controller;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\UserManager
     */
    private $user_manager;
    /**
     * @var \CSRFSynchronizerToken|M\LegacyMockInterface|M\MockInterface
     */
    private $csrf_token;

    protected function setUp(): void
    {
        $this->user_manager = M::mock(\UserManager::class);
        $this->csrf_token   = M::mock(\CSRFSynchronizerToken::class);
        $this->controller   = new AuthorizationEndpointPostController(
            HTTPFactoryBuilder::responseFactory(),
            $this->user_manager,
            new RedirectURIBuilder(HTTPFactoryBuilder::URIFactory()),
            $this->csrf_token,
            M::mock(EmitterInterface::class)
        );
    }

    public function testHandleThrowsForbiddenWhenUserIsAnonymous(): void
    {
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn(
            UserTestBuilder::anAnonymousUser()->build()
        );

        $this->expectException(ForbiddenException::class);
        $this->controller->handle(new NullServerRequest());
    }

    public function testHandleThrowsForbiddenWhenRequestHasNoBodyParams(): void
    {
        $this->user_manager->shouldReceive('getCurrentUser')
            ->andReturn(UserTestBuilder::aUser()->withId(102)->build());
        $request = new NullServerRequest();

        $this->expectException(ForbiddenException::class);
        $this->controller->handle($request);
    }

    public function testHandleThrowsForbiddenWhenNoRedirectURI(): void
    {
        $this->user_manager->shouldReceive('getCurrentUser')
            ->andReturn(UserTestBuilder::aUser()->withId(102)->build());
        $request = (new NullServerRequest())->withParsedBody(['state' => 'xyz']);

        $this->expectException(ForbiddenException::class);
        $this->controller->handle($request);
    }

    public function testHandleRedirectsToRedirectURIWithAuthorizationCode(): void
    {
        $this->user_manager->shouldReceive('getCurrentUser')
            ->andReturn(UserTestBuilder::aUser()->withId(102)->build());
        $request = (new NullServerRequest())->withParsedBody(
            ['redirect_uri' => 'https://example.com']
        );
        $this->csrf_token->shouldReceive('check')->once();

        $response = $this->controller->handle($request);
        $this->assertSame(302, $response->getStatusCode());
        $location = $response->getHeaderLine('Location');
        $this->assertStringContainsString('https://example.com', $location);
        $this->assertStringContainsString('code=', $location);
    }

    public function testHandlePassesStateParameterWhenPresent(): void
    {
        $this->user_manager->shouldReceive('getCurrentUser')
            ->andReturn(UserTestBuilder::aUser()->withId(102)->build());
        $request = (new NullServerRequest())->withParsedBody(
            ['redirect_uri' => 'https://example.com', 'state' => 'xyz']
        );
        $this->csrf_token->shouldReceive('check')->once();

        $response = $this->controller->handle($request);
        $this->assertSame(302, $response->getStatusCode());
        $location = $response->getHeaderLine('Location');
        $this->assertStringContainsString('https://example.com', $location);
        $this->assertStringContainsString('state=xyz', $location);
        $this->assertStringContainsString('code=', $location);
    }
}
