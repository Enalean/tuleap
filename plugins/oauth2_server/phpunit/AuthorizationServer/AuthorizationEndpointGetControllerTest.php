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
use Tuleap\Layout\BaseLayout;
use Tuleap\OAuth2Server\App\AppFactory;
use Tuleap\OAuth2Server\App\ClientIdentifier;
use Tuleap\OAuth2Server\App\OAuth2App;
use Tuleap\OAuth2Server\App\OAuth2AppNotFoundException;
use Tuleap\Request\ForbiddenException;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\TemplateRendererFactoryBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class AuthorizationEndpointGetControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration, TemporaryTestDirectory;

    /** @var AuthorizationEndpointGetController */
    private $controller;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|AppFactory
     */
    private $app_factory;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\UserManager
     */
    private $user_manager;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\URLRedirect
     */
    private $redirect;

    protected function setUp(): void
    {
        $this->app_factory  = M::mock(AppFactory::class);
        $this->redirect     = M::mock(\URLRedirect::class);
        $this->user_manager = M::mock(\UserManager::class);
        $this->controller  = new AuthorizationEndpointGetController(
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory(),
            TemplateRendererFactoryBuilder::get()->withPath($this->getTmpDir())->build(),
            $this->user_manager,
            $this->app_factory,
            $this->redirect,
            \Mockery::mock(EmitterInterface::class)
        );
    }

    public function testProcessRedirectsAnonymousToLogin(): void
    {
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn(
            UserTestBuilder::anAnonymousUser()->build()
        );
        $this->redirect->shouldReceive('buildReturnToLogin')->andReturn('/login');

        $response = $this->controller->handle(new NullServerRequest());

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
    }

    public function testProcessThrowsWhenClientIdIsMissing(): void
    {
        $user    = UserTestBuilder::aUser()->withId(102)->build();
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn($user);
        $this->expectException(ForbiddenException::class);
        $this->controller->handle(new NullServerRequest());
    }

    public function testProcessThrowsWhenClientIdHasWrongFormat(): void
    {
        $user    = UserTestBuilder::aUser()->withId(102)->build();
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn($user);
        $this->expectException(ForbiddenException::class);
        $request = new NullServerRequest();
        $this->controller->handle($request->withQueryParams(['client_id' => 'bad_client_id']));
    }

    public function testProcessThrowsWhenNoAppMatchesClientId(): void
    {
        $user    = UserTestBuilder::aUser()->withId(102)->build();
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn($user);
        $request = (new NullServerRequest())->withQueryParams(['client_id' => 'tlp-client-id-1']);
        $this->app_factory->shouldReceive('getAppMatchingClientId')
            ->once()
            ->andThrow(new OAuth2AppNotFoundException(ClientIdentifier::fromClientId('tlp-client-id-1')));

        $this->expectException(ForbiddenException::class);
        $this->controller->handle($request);
    }

    public function testProcessRendersAuthorizationForm(): void
    {
        $user    = UserTestBuilder::aUser()->withId(102)->build();
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn($user);
        $project = M::mock(\Project::class)->shouldReceive('getPublicName')
            ->andReturn('Test Project')
            ->getMock();
        $request = (new NullServerRequest())->withQueryParams(['client_id' => 'tlp-client-id-1']);
        $this->app_factory->shouldReceive('getAppMatchingClientId')
            ->once()
            ->andReturn(new OAuth2App(1, 'Jenkins', $project));

        $response = $this->controller->handle($request->withAttribute(BaseLayout::class, LayoutBuilder::build()));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Authorize application', $response->getBody()->getContents());
    }
}
