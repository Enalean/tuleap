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
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\OAuth2Server\App\AppFactory;
use Tuleap\OAuth2Server\App\ClientIdentifier;
use Tuleap\OAuth2Server\App\OAuth2App;
use Tuleap\OAuth2Server\App\OAuth2AppNotFoundException;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class AuthorizationEndpointGetControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var AuthorizationEndpointGetController */
    private $controller;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|AuthorizationFormRenderer
     */
    private $form_renderer;
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
    /**
     * @var M\LegacyMockInterface|M\MockInterface|ScopeExtractor
     */
    private $scope_extractor;

    protected function setUp(): void
    {
        $this->form_renderer   = M::mock(AuthorizationFormRenderer::class);
        $this->app_factory     = M::mock(AppFactory::class);
        $this->redirect        = M::mock(\URLRedirect::class);
        $this->user_manager    = M::mock(\UserManager::class);
        $this->scope_extractor = M::mock(ScopeExtractor::class);
        $this->controller      = new AuthorizationEndpointGetController(
            HTTPFactoryBuilder::responseFactory(),
            $this->form_renderer,
            $this->user_manager,
            $this->app_factory,
            $this->redirect,
            new RedirectURIBuilder(HTTPFactoryBuilder::URIFactory()),
            $this->scope_extractor,
            \Mockery::mock(EmitterInterface::class)
        );
    }

    protected function tearDown(): void
    {
        if (isset($GLOBALS['_SESSION'])) {
            unset($GLOBALS['_SESSION']);
        }
    }

    public function testHandleRedirectsAnonymousToLogin(): void
    {
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn(
            UserTestBuilder::anAnonymousUser()->build()
        );
        $this->redirect->shouldReceive('buildReturnToLogin')->andReturn('/login');

        $response = $this->controller->handle(new NullServerRequest());

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
    }

    /**
     * @dataProvider dataProviderInvalidEssentialQueryParameters
     */
    public function testHandleThrowsForbiddenWhenEssentialQueryParametersAreInvalid(array $query_parameters): void
    {
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn($user);
        $request = (new NullServerRequest())->withQueryParams($query_parameters);
        $project = new \Project(['group_id' => 101, 'group_name' => 'Rest Project']);
        $this->app_factory->shouldReceive('getAppMatchingClientId')
            ->with(
                M::on(
                    static function (ClientIdentifier $identifier) {
                        return 'tlp-client-id-1' === $identifier->toString();
                    }
                )
            )
            ->andReturn(new OAuth2App(1, 'Jenkins', 'https://example.com/redirect', $project));
        $this->app_factory->shouldReceive('getAppMatchingClientId')
            ->with(
                M::on(
                    static function (ClientIdentifier $identifier) {
                        return 'tlp-client-id-404' === $identifier->toString();
                    }
                )
            )
            ->andThrow(new OAuth2AppNotFoundException(ClientIdentifier::fromClientId('tlp-client-id-404')));

        $this->expectException(ForbiddenException::class);
        $this->controller->handle($request);
    }

    public function dataProviderInvalidEssentialQueryParameters(): array
    {
        return [
            'No client ID'                                     => [[]],
            'Client ID has wrong format'                       => [['client_id' => 'bad_client_id']],
            'Client ID matches no App'                         => [['client_id' => 'tlp-client-id-404']],
            'No redirect URI'                                  => [['client_id' => 'tlp-client-id-1']],
            "Redirect URI does not match App's registered URI" => [['client_id' => 'tlp-client-id-1', 'redirect_uri' => 'https://example.com/invalid-redirect-uri']]
        ];
    }

    /**
     * @dataProvider dataProviderInvalidQueryParameters
     */
    public function testHandleRedirectsWithErrorWhenQueryParametersAreInvalid(
        array $query_parameters,
        string $expected_redirection_url
    ): void {
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn($user);
        $project = new \Project(['group_id' => 101, 'group_name' => 'Rest Project']);
        $request = (new NullServerRequest())->withQueryParams($query_parameters);
        $this->app_factory->shouldReceive('getAppMatchingClientId')
            ->once()
            ->andReturn(new OAuth2App(1, 'Jenkins', $query_parameters['redirect_uri'], $project));
        $this->scope_extractor->shouldReceive('extractScopes')
            ->andThrow(new InvalidOAuth2ScopeException());

        $response = $this->controller->handle($request);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertSame($expected_redirection_url, $response->getHeaderLine('Location'));
    }

    public function dataProviderInvalidQueryParameters(): array
    {
        return [
            'No response type'                     => [
                ['client_id' => 'tlp-client-id-1', 'redirect_uri' => 'https://example.com/redirect?key=value'],
                'https://example.com/redirect?key=value&error=invalid_request'
            ],
            'Response type is not allowed'         => [
                ['client_id' => 'tlp-client-id-1', 'redirect_uri' => 'https://example.com/redirect?key=value', 'response_type' => 'invalid_response_type'],
                'https://example.com/redirect?key=value&error=invalid_request'
            ],
            'State parameter is passed unmodified' => [
                ['client_id' => 'tlp-client-id-1', 'redirect_uri' => 'https://example.com/redirect?key=value', 'response_type' => 'invalid_response_type', 'state' => 'xyz'],
                'https://example.com/redirect?key=value&state=xyz&error=invalid_request'
            ],
            'Scope is unknown'                     => [
                ['client_id' => 'tlp-client-id-1', 'redirect_uri' => 'https://example.com/redirect?key=value', 'response_type' => 'code', 'state' => 'xyz', 'scope' => 'invalid_scope'],
                'https://example.com/redirect?key=value&state=xyz&error=invalid_scope'
            ]
        ];
    }

    public function testHandleRendersAuthorizationForm(): void
    {
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn($user);
        $project = M::mock(\Project::class)->shouldReceive('getPublicName')
            ->andReturn('Test Project')
            ->getMock();
        $request = (new NullServerRequest())->withQueryParams(
            [
                'client_id'     => 'tlp-client-id-1',
                'redirect_uri'  => 'https://example.com/redirect',
                'response_type' => 'code'
            ]
        );
        $this->app_factory->shouldReceive('getAppMatchingClientId')
            ->once()
            ->andReturn(new OAuth2App(1, 'Jenkins', 'https://example.com/redirect', $project));
        $this->scope_extractor->shouldReceive('extractScopes')
            ->once()
            ->andReturn([M::mock(AuthenticationScope::class)]);
        $this->form_renderer->shouldReceive('renderForm')->once();

        $this->controller->handle($request->withAttribute(BaseLayout::class, LayoutBuilder::build()));
    }
}
