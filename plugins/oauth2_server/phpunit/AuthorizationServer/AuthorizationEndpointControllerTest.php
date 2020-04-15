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
use Tuleap\OAuth2Server\AuthorizationServer\PKCE\OAuth2PKCEInformationExtractionException;
use Tuleap\OAuth2Server\AuthorizationServer\PKCE\PKCEInformationExtractor;
use Tuleap\OAuth2Server\OAuth2TestScope;
use Tuleap\OAuth2Server\RefreshToken\OAuth2OfflineAccessScope;
use Tuleap\OAuth2Server\Scope\InvalidOAuth2ScopeException;
use Tuleap\OAuth2Server\Scope\ScopeExtractor;
use Tuleap\OAuth2Server\User\AuthorizationComparator;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class AuthorizationEndpointControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var AuthorizationEndpointController */
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
     * @var M\LegacyMockInterface|M\MockInterface|ScopeExtractor
     */
    private $scope_extractor;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|AuthorizationCodeResponseFactory
     */
    private $response_factory;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|AuthorizationComparator
     */
    private $comparator;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|PKCEInformationExtractor
     */
    private $pkce_information_extractor;

    protected function setUp(): void
    {
        $this->form_renderer              = M::mock(AuthorizationFormRenderer::class);
        $this->app_factory                = M::mock(AppFactory::class);
        $this->user_manager               = M::mock(\UserManager::class);
        $this->scope_extractor            = M::mock(ScopeExtractor::class);
        $this->response_factory           = M::mock(AuthorizationCodeResponseFactory::class);
        $this->comparator                 = M::mock(AuthorizationComparator::class);
        $this->pkce_information_extractor = M::mock(PKCEInformationExtractor::class);
        $this->controller                 = new AuthorizationEndpointController(
            $this->form_renderer,
            $this->user_manager,
            $this->app_factory,
            $this->scope_extractor,
            $this->response_factory,
            $this->comparator,
            $this->pkce_information_extractor,
            new PromptParameterValuesExtractor(),
            OAuth2OfflineAccessScope::fromItself(),
            \Mockery::mock(EmitterInterface::class)
        );
    }

    protected function tearDown(): void
    {
        if (isset($GLOBALS['_SESSION'])) {
            unset($GLOBALS['_SESSION']);
        }
    }

    public function testHandleRedirectsAnonymousToLoginWhenInteractionAreAllowed(): void
    {
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn(
            UserTestBuilder::anAnonymousUser()->build()
        );
        $project = new \Project(['group_id' => 101, 'group_name' => 'Rest Project']);
        $request = (new NullServerRequest())->withQueryParams(
            [
                'client_id'      => 'tlp-client-id-1',
                'redirect_uri'   => 'https://example.com/redirect',
                'response_type'  => 'code',
            ]
        );
        $this->app_factory->shouldReceive('getAppMatchingClientId')
            ->once()
            ->andReturn(new OAuth2App(1, 'Jenkins', 'https://example.com/redirect', true, $project));
        $response = HTTPFactoryBuilder::responseFactory()->createResponse(302);
        $this->response_factory->shouldReceive('createRedirectToLoginResponse')->once()->andReturn($response);

        $this->assertSame($response, $this->controller->handle($request));
    }

    public function testRejectsAnonymousUserWithAnErrorWhenNoInteractionAreAllowed(): void
    {
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn(
            UserTestBuilder::anAnonymousUser()->build()
        );
        $project = new \Project(['group_id' => 101, 'group_name' => 'Rest Project']);
        $request = (new NullServerRequest())->withQueryParams(
            [
                'client_id'      => 'tlp-client-id-1',
                'redirect_uri'   => 'https://example.com/redirect',
                'response_type'  => 'code',
                'prompt'         => 'none',
            ]
        );
        $this->app_factory->shouldReceive('getAppMatchingClientId')
            ->once()
            ->andReturn(new OAuth2App(1, 'Jenkins', 'https://example.com/redirect', true, $project));
        $response = HTTPFactoryBuilder::responseFactory()->createResponse(302);
        $this->response_factory->shouldReceive('createErrorResponse')
            ->with('login_required', 'https://example.com/redirect', null)
            ->once()->andReturn($response);

        $this->assertSame($response, $this->controller->handle($request));
    }

    public function testHandleRedirectsToLoginWhenPromptParameterRequiresIt(): void
    {
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn(
            UserTestBuilder::aUser()->withId(102)->build()
        );
        $project = new \Project(['group_id' => 101, 'group_name' => 'Rest Project']);
        $request = (new NullServerRequest())->withQueryParams(
            [
                'client_id'      => 'tlp-client-id-1',
                'redirect_uri'   => 'https://example.com/redirect',
                'response_type'  => 'code',
                'prompt'         => 'login',
            ]
        );
        $this->app_factory->shouldReceive('getAppMatchingClientId')
            ->once()
            ->andReturn(new OAuth2App(1, 'Jenkins', 'https://example.com/redirect', true, $project));
        $response = HTTPFactoryBuilder::responseFactory()->createResponse(302);
        $this->response_factory->shouldReceive('createRedirectToLoginResponse')->once()->andReturn($response);

        $this->assertSame($response, $this->controller->handle($request));
    }

    public function testHandleRedirectsToLoginWhenMaxAgeParameterRequiresIt(): void
    {
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn(
            UserTestBuilder::aUser()->withId(102)->build()
        );
        $this->user_manager->shouldReceive('getUserAccessInfo')->andReturn(['last_auth_success' => 1]);
        $project = new \Project(['group_id' => 101, 'group_name' => 'Rest Project']);
        $request = (new NullServerRequest())->withQueryParams(
            [
                'client_id'      => 'tlp-client-id-1',
                'redirect_uri'   => 'https://example.com/redirect',
                'response_type'  => 'code',
                'max_age'        => '1',
            ]
        );
        $this->app_factory->shouldReceive('getAppMatchingClientId')
            ->once()
            ->andReturn(new OAuth2App(1, 'Jenkins', 'https://example.com/redirect', true, $project));
        $response = HTTPFactoryBuilder::responseFactory()->createResponse(302);
        $this->response_factory->shouldReceive('createRedirectToLoginResponse')->once()->andReturn($response);

        $this->assertSame($response, $this->controller->handle($request));
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
            ->andReturn(new OAuth2App(1, 'Jenkins', 'https://example.com/redirect', true, $project));
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

    public function testHandlesThrowsForbiddenWhenPOSTRequestHasNoBody(): void
    {
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn($user);
        $request = (new NullServerRequest())->withParsedBody(null)->withMethod('POST');

        $this->expectException(ForbiddenException::class);
        $this->controller->handle($request);
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
            ->andReturn(new OAuth2App(1, 'Jenkins', $query_parameters['redirect_uri'], true, $project));
        $this->scope_extractor->shouldReceive('extractScopes')
            ->andThrow(new InvalidOAuth2ScopeException());

        $response = HTTPFactoryBuilder::responseFactory()->createResponse(302);
        $this->response_factory->shouldReceive('createErrorResponse')->andReturn($response);

        $this->assertSame($response, $this->controller->handle($request));
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
            'Scope is not given'                   => [
                ['client_id' => 'tlp-client-id-1', 'redirect_uri' => 'https://example.com/redirect?key=value', 'response_type' => 'code', 'state' => 'xyz'],
                'https://example.com/redirect?key=value&state=xyz&error=invalid_scope'
            ],
            'Scope is unknown'                     => [
                ['client_id' => 'tlp-client-id-1', 'redirect_uri' => 'https://example.com/redirect?key=value', 'response_type' => 'code', 'state' => 'xyz', 'scope' => 'invalid_scope'],
                'https://example.com/redirect?key=value&state=xyz&error=invalid_scope'
            ],
            'Prompt parameter is not valid'        => [
                ['client_id' => 'tlp-client-id-1', 'redirect_uri' => 'https://example.com/redirect?key=value', 'response_type' => 'code', 'state' => 'xyz', 'prompt' => 'login none'],
                'https://example.com/redirect?key=value&state=xyz&error=invalid_request'
            ],
        ];
    }

    public function testHandleRedirectsWithErrorPKCECodeChallengeExtractionFails(): void
    {
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn($user);
        $project = new \Project(['group_id' => 101, 'group_name' => 'Rest Project']);
        $request = (new NullServerRequest())->withQueryParams(
            [
                'client_id'      => 'tlp-client-id-1',
                'redirect_uri'   => 'https://example.com/redirect',
                'response_type'  => 'code',
                'state'          => 'xyz',
                'code_challenge' => 'failure',
                'scope'          => 'scopename:read'
            ]
        );
        $this->app_factory->shouldReceive('getAppMatchingClientId')
            ->once()
            ->andReturn(new OAuth2App(1, 'Jenkins', 'https://example.com/redirect', true, $project));
        $this->scope_extractor->shouldReceive('extractScopes')
            ->once()
            ->andReturn([M::mock(AuthenticationScope::class)]);
        $this->pkce_information_extractor->shouldReceive('extractCodeChallenge')->andThrow(
            new class extends \RuntimeException implements OAuth2PKCEInformationExtractionException
            {

            }
        );

        $response = HTTPFactoryBuilder::responseFactory()->createResponse(302);
        $this->response_factory->shouldReceive('createErrorResponse')->andReturn($response);

        $this->assertSame($response, $this->controller->handle($request));
    }

    /**
     * @dataProvider dataProviderSupportedRequestHTTPMethod
     */
    public function testHandleRedirectsWithAuthorizationCodeWhenAPreviousAuthorizationHasBeenGranted(string $request_http_method): void
    {
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn($user);
        $project = M::mock(\Project::class)->shouldReceive('getPublicName')
            ->andReturn('Test Project')
            ->getMock();
        $request_params = [
            'client_id'     => 'tlp-client-id-1',
            'redirect_uri'  => 'https://example.com/redirect',
            'response_type' => 'code',
            'state'         => 'xyz',
            'scope'         => 'scopename:read',
        ];
        if ($request_http_method === 'POST') {
            $request = (new NullServerRequest())->withParsedBody($request_params)->withMethod('POST');
        } else {
            $request = (new NullServerRequest())->withQueryParams($request_params)->withMethod('GET');
        }

        $this->app_factory->shouldReceive('getAppMatchingClientId')
            ->once()
            ->andReturn(new OAuth2App(1, 'Jenkins', 'https://example.com/redirect', true, $project));
        $this->scope_extractor->shouldReceive('extractScopes')
            ->once()
            ->andReturn([OAuth2TestScope::fromItself()]);
        $this->comparator->shouldReceive('areRequestedScopesAlreadyGranted')
            ->once()
            ->andReturnTrue();
        $this->pkce_information_extractor->shouldReceive('extractCodeChallenge')->andReturn('extracted_code_challenge');
        $this->form_renderer->shouldNotReceive('renderForm');

        $response = HTTPFactoryBuilder::responseFactory()->createResponse(302);
        $this->response_factory->shouldReceive('createSuccessfulResponse')->andReturn($response);

        $this->assertSame($response, $this->controller->handle($request));
    }

    public function testRendersAuthorizationFormWhenAPreviousAuthorizationHasBeenGrantedButConsentIsRequiredByPromptParameter(): void
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
                'response_type' => 'code',
                'state'         => 'xyz',
                'scope'         => 'scopename:read',
                'prompt'        => 'consent',
            ]
        );

        $this->app_factory->shouldReceive('getAppMatchingClientId')
            ->once()
            ->andReturn(new OAuth2App(1, 'Jenkins', 'https://example.com/redirect', true, $project));
        $this->scope_extractor->shouldReceive('extractScopes')
            ->once()
            ->andReturn([M::mock(AuthenticationScope::class)]);
        $this->comparator->shouldReceive('areRequestedScopesAlreadyGranted')
            ->andReturnTrue();
        $this->pkce_information_extractor->shouldReceive('extractCodeChallenge')->andReturn('extracted_code_challenge');

        $this->form_renderer->shouldReceive('renderForm')->once();

        $this->controller->handle($request->withAttribute(BaseLayout::class, LayoutBuilder::build()));
    }

    public function testRendersAuthorizationFormWhenAPreviousAuthorizationHasBeenGrantedButConsentIsRequiredBecauseOfflineAccessScopeIsAsked(): void
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
                'response_type' => 'code',
                'state'         => 'xyz',
                'scope'         => 'offline_access',
            ]
        );

        $this->app_factory->shouldReceive('getAppMatchingClientId')
            ->once()
            ->andReturn(new OAuth2App(1, 'Jenkins', 'https://example.com/redirect', true, $project));
        $this->scope_extractor->shouldReceive('extractScopes')
            ->once()
            ->andReturn([OAuth2OfflineAccessScope::fromItself()]);
        $this->comparator->shouldReceive('areRequestedScopesAlreadyGranted')
            ->once()
            ->andReturnTrue();
        $this->pkce_information_extractor->shouldReceive('extractCodeChallenge')->andReturn('extracted_code_challenge');

        $this->form_renderer->shouldReceive('renderForm')->once();

        $this->controller->handle($request->withAttribute(BaseLayout::class, LayoutBuilder::build()));
    }

    public function testHandlesRedirectWithAnInteractionRequiredErrorWhenUserNeedsToConsent(): void
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
                'response_type' => 'code',
                'state'         => 'xyz',
                'scope'         => 'scopename:read',
                'prompt'        => 'none',
            ]
        );

        $this->app_factory->shouldReceive('getAppMatchingClientId')
            ->once()
            ->andReturn(new OAuth2App(1, 'Jenkins', 'https://example.com/redirect', true, $project));
        $this->scope_extractor->shouldReceive('extractScopes')
            ->once()
            ->andReturn([M::mock(AuthenticationScope::class)]);
        $this->comparator->shouldReceive('areRequestedScopesAlreadyGranted')
            ->once()
            ->andReturnFalse();
        $this->pkce_information_extractor->shouldReceive('extractCodeChallenge')->andReturn('extracted_code_challenge');

        $response = HTTPFactoryBuilder::responseFactory()->createResponse(302);
        $this->response_factory->shouldReceive('createErrorResponse')
            ->with('interaction_required', 'https://example.com/redirect', 'xyz')
            ->once()->andReturn($response);

        $this->assertSame($response, $this->controller->handle($request));
    }

    /**
     * @dataProvider dataProviderSupportedRequestHTTPMethod
     */
    public function testHandleRendersAuthorizationForm(string $request_http_method): void
    {
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn($user);
        $project = M::mock(\Project::class)->shouldReceive('getPublicName')
            ->andReturn('Test Project')
            ->getMock();
        $request        = (new NullServerRequest())->withMethod($request_http_method);
        $request_params = [
            'client_id'     => 'tlp-client-id-1',
            'redirect_uri'  => 'https://example.com/redirect',
            'response_type' => 'code',
            'state'         => 'xyz',
            'scope'         => 'scopename:read',
        ];
        if ($request_http_method === 'POST') {
            $request = $request->withParsedBody($request_params);
        } else {
            $request = $request->withQueryParams($request_params);
        }
        $this->app_factory->shouldReceive('getAppMatchingClientId')
            ->once()
            ->andReturn(new OAuth2App(1, 'Jenkins', 'https://example.com/redirect', true, $project));
        $this->scope_extractor->shouldReceive('extractScopes')
            ->once()
            ->andReturn([M::mock(AuthenticationScope::class)]);
        $this->comparator->shouldReceive('areRequestedScopesAlreadyGranted')
            ->once()
            ->andReturnFalse();
        $this->pkce_information_extractor->shouldReceive('extractCodeChallenge')->andReturn('extracted_code_challenge');
        $this->form_renderer->shouldReceive('renderForm')->once();

        $this->controller->handle($request->withAttribute(BaseLayout::class, LayoutBuilder::build()));
    }

    public function dataProviderSupportedRequestHTTPMethod(): array
    {
        return [
            ['GET'],
            ['POST'],
        ];
    }

    public function testHandleRendersAuthorizationFormWhenMaxAgeParameterIsSetButTheUserHasAuthenticatedRecently(): void
    {
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn($user);
        $this->user_manager->shouldReceive('getUserAccessInfo')->andReturn(
            ['last_auth_success' => (new \DateTimeImmutable())->sub(new \DateInterval('PT60S'))->getTimestamp()]
        );
        $project = M::mock(\Project::class)->shouldReceive('getPublicName')
            ->andReturn('Test Project')
            ->getMock();
        $request = (new NullServerRequest())->withQueryParams(
            [
                'client_id'     => 'tlp-client-id-1',
                'redirect_uri'  => 'https://example.com/redirect',
                'response_type' => 'code',
                'state'         => 'xyz',
                'scope'         => 'scopename:read',
                'max_age'       => '3600'
            ]
        );
        $this->app_factory->shouldReceive('getAppMatchingClientId')
            ->once()
            ->andReturn(new OAuth2App(1, 'Jenkins', 'https://example.com/redirect', true, $project));
        $this->scope_extractor->shouldReceive('extractScopes')
            ->once()
            ->andReturn([M::mock(AuthenticationScope::class)]);
        $this->comparator->shouldReceive('areRequestedScopesAlreadyGranted')
            ->once()
            ->andReturnFalse();
        $this->pkce_information_extractor->shouldReceive('extractCodeChallenge')->andReturn('extracted_code_challenge');
        $this->form_renderer->shouldReceive('renderForm')->once();

        $this->controller->handle($request->withAttribute(BaseLayout::class, LayoutBuilder::build()));
    }
}
