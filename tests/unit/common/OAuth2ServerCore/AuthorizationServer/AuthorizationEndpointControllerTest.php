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

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Log\NullLogger;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\OAuth2ServerCore\App\AppFactory;
use Tuleap\OAuth2ServerCore\App\ClientIdentifier;
use Tuleap\OAuth2ServerCore\App\OAuth2App;
use Tuleap\OAuth2ServerCore\App\OAuth2AppNotFoundException;
use Tuleap\OAuth2ServerCore\AuthorizationServer\PKCE\OAuth2PKCEInformationExtractionException;
use Tuleap\OAuth2ServerCore\AuthorizationServer\PKCE\PKCEInformationExtractor;
use Tuleap\OAuth2ServerCore\OAuth2TestScope;
use Tuleap\OAuth2ServerCore\Scope\InvalidOAuth2ScopeException;
use Tuleap\OAuth2ServerCore\Scope\ScopeExtractor;
use Tuleap\OAuth2ServerCore\RefreshToken\OAuth2OfflineAccessScope;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AuthorizationEndpointControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private AuthorizationEndpointController $controller;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ConsentRequiredResponseBuilder
     */
    private $form_renderer_builder;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AppFactory
     */
    private $app_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\UserManager
     */
    private $user_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ScopeExtractor
     */
    private $scope_extractor;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AuthorizationCodeResponseFactory
     */
    private $response_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PKCEInformationExtractor
     */
    private $pkce_information_extractor;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ConsentChecker
     */
    private $consent_checker;

    protected function setUp(): void
    {
        $this->form_renderer_builder      = $this->createMock(ConsentRequiredResponseBuilder::class);
        $this->app_factory                = $this->createMock(AppFactory::class);
        $this->user_manager               = $this->createMock(\UserManager::class);
        $this->scope_extractor            = $this->createMock(ScopeExtractor::class);
        $this->response_factory           = $this->createMock(AuthorizationCodeResponseFactory::class);
        $this->pkce_information_extractor = $this->createMock(PKCEInformationExtractor::class);
        $this->consent_checker            = $this->createMock(ConsentChecker::class);

        $this->controller = new AuthorizationEndpointController(
            $this->form_renderer_builder,
            $this->user_manager,
            $this->app_factory,
            $this->scope_extractor,
            $this->response_factory,
            $this->pkce_information_extractor,
            new PromptParameterValuesExtractor(),
            $this->consent_checker,
            new NullLogger(),
            $this->createMock(EmitterInterface::class)
        );
    }

    public function testHandleRedirectsAnonymousToLoginWhenInteractionAreAllowed(): void
    {
        $this->user_manager->method('getCurrentUser')->willReturn(
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
        $this->app_factory->expects($this->once())->method('getAppMatchingClientId')
            ->willReturn(new OAuth2App(1, 'Jenkins', 'https://example.com/redirect', true, $project));
        $response = HTTPFactoryBuilder::responseFactory()->createResponse(302);
        $this->response_factory->expects($this->once())->method('createRedirectToLoginResponse')->willReturn($response);

        self::assertSame($response, $this->controller->handle($request));
    }

    public function testRejectsAnonymousUserWithAnErrorWhenNoInteractionAreAllowed(): void
    {
        $this->user_manager->method('getCurrentUser')->willReturn(
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
        $this->app_factory->expects($this->once())->method('getAppMatchingClientId')
            ->willReturn(new OAuth2App(1, 'Jenkins', 'https://example.com/redirect', true, $project));
        $response = HTTPFactoryBuilder::responseFactory()->createResponse(302);
        $this->response_factory->expects($this->once())->method('createErrorResponse')
            ->with('login_required', 'https://example.com/redirect', null)
            ->willReturn($response);

        self::assertSame($response, $this->controller->handle($request));
    }

    public function testHandleRedirectsToLoginWhenPromptParameterRequiresIt(): void
    {
        $this->user_manager->method('getCurrentUser')->willReturn(
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
        $this->app_factory->expects($this->once())->method('getAppMatchingClientId')
            ->willReturn(new OAuth2App(1, 'Jenkins', 'https://example.com/redirect', true, $project));
        $response = HTTPFactoryBuilder::responseFactory()->createResponse(302);
        $this->response_factory->expects($this->once())->method('createRedirectToLoginResponse')->willReturn($response);

        self::assertSame($response, $this->controller->handle($request));
    }

    public function testHandleRedirectsToLoginWhenMaxAgeParameterRequiresIt(): void
    {
        $this->user_manager->method('getCurrentUser')->willReturn(
            UserTestBuilder::aUser()->withId(102)->build()
        );
        $this->user_manager->method('getUserAccessInfo')->willReturn(['last_auth_success' => 1]);
        $project = new \Project(['group_id' => 101, 'group_name' => 'Rest Project']);
        $request = (new NullServerRequest())->withQueryParams(
            [
                'client_id'      => 'tlp-client-id-1',
                'redirect_uri'   => 'https://example.com/redirect',
                'response_type'  => 'code',
                'max_age'        => '1',
            ]
        );
        $this->app_factory->expects($this->once())->method('getAppMatchingClientId')
            ->willReturn(new OAuth2App(1, 'Jenkins', 'https://example.com/redirect', true, $project));
        $response = HTTPFactoryBuilder::responseFactory()->createResponse(302);
        $this->response_factory->expects($this->once())->method('createRedirectToLoginResponse')->willReturn($response);

        self::assertSame($response, $this->controller->handle($request));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderInvalidEssentialQueryParameters')]
    public function testHandleThrowsForbiddenWhenEssentialQueryParametersAreInvalid(array $query_parameters): void
    {
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->user_manager->method('getCurrentUser')->willReturn($user);
        $request = (new NullServerRequest())->withQueryParams($query_parameters);
        $project = new \Project(['group_id' => 101, 'group_name' => 'Rest Project']);
        $this->app_factory->method('getAppMatchingClientId')->willReturnCallback(
            static function (ClientIdentifier $identifier) use ($project): OAuth2App {
                $raw_string_id = $identifier->toString();

                if ('tlp-client-id-1' === $raw_string_id) {
                    return new OAuth2App(1, 'Jenkins', 'https://example.com/redirect', true, $project);
                }

                if ('tlp-client-id-404' === $raw_string_id) {
                    throw new OAuth2AppNotFoundException(ClientIdentifier::fromClientId('tlp-client-id-404'));
                }

                throw new \LogicException(sprintf('Client ID %s was not expected', $raw_string_id));
            }
        );

        $this->expectException(ForbiddenException::class);
        $this->controller->handle($request);
    }

    public static function dataProviderInvalidEssentialQueryParameters(): array
    {
        return [
            'No client ID'                                     => [[]],
            'Client ID has wrong format'                       => [['client_id' => 'bad_client_id']],
            'Client ID matches no App'                         => [['client_id' => 'tlp-client-id-404']],
            'No redirect URI'                                  => [['client_id' => 'tlp-client-id-1']],
            "Redirect URI does not match App's registered URI" => [['client_id' => 'tlp-client-id-1', 'redirect_uri' => 'https://example.com/invalid-redirect-uri']],
        ];
    }

    public function testHandlesThrowsForbiddenWhenPOSTRequestHasNoBody(): void
    {
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->user_manager->method('getCurrentUser')->willReturn($user);
        $request = (new NullServerRequest())->withParsedBody(null)->withMethod('POST');

        $this->expectException(ForbiddenException::class);
        $this->controller->handle($request);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderInvalidQueryParameters')]
    public function testHandleRedirectsWithErrorWhenQueryParametersAreInvalid(
        array $query_parameters,
        string $expected_redirection_url,
    ): void {
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->user_manager->method('getCurrentUser')->willReturn($user);
        $project = new \Project(['group_id' => 101, 'group_name' => 'Rest Project']);
        $request = (new NullServerRequest())->withQueryParams($query_parameters);
        $this->app_factory->expects($this->once())->method('getAppMatchingClientId')
            ->willReturn(new OAuth2App(1, 'Jenkins', $query_parameters['redirect_uri'], true, $project));
        $this->scope_extractor->method('extractScopes')
            ->willThrowException(InvalidOAuth2ScopeException::scopeDoesNotExist(OAuth2ScopeIdentifier::fromIdentifierKey('donotexist')));

        $response = HTTPFactoryBuilder::responseFactory()->createResponse(302);
        $this->response_factory->method('createErrorResponse')->willReturn($response);

        self::assertSame($response, $this->controller->handle($request));
    }

    public static function dataProviderInvalidQueryParameters(): array
    {
        return [
            'No response type'                         => [
                ['client_id' => 'tlp-client-id-1', 'redirect_uri' => 'https://example.com/redirect?key=value'],
                'https://example.com/redirect?key=value&error=invalid_request',
            ],
            'Response type is not allowed'             => [
                ['client_id' => 'tlp-client-id-1', 'redirect_uri' => 'https://example.com/redirect?key=value', 'response_type' => 'invalid_response_type'],
                'https://example.com/redirect?key=value&error=invalid_request',
            ],
            'State parameter is passed unmodified'     => [
                ['client_id' => 'tlp-client-id-1', 'redirect_uri' => 'https://example.com/redirect?key=value', 'response_type' => 'invalid_response_type', 'state' => 'xyz'],
                'https://example.com/redirect?key=value&state=xyz&error=invalid_request',
            ],
            'Scope is not given'                       => [
                ['client_id' => 'tlp-client-id-1', 'redirect_uri' => 'https://example.com/redirect?key=value', 'response_type' => 'code', 'state' => 'xyz'],
                'https://example.com/redirect?key=value&state=xyz&error=invalid_scope',
            ],
            'Scope is unknown'                         => [
                ['client_id' => 'tlp-client-id-1', 'redirect_uri' => 'https://example.com/redirect?key=value', 'response_type' => 'code', 'state' => 'xyz', 'scope' => 'invalid_scope'],
                'https://example.com/redirect?key=value&state=xyz&error=invalid_scope',
            ],
            'Prompt parameter is not valid'            => [
                ['client_id' => 'tlp-client-id-1', 'redirect_uri' => 'https://example.com/redirect?key=value', 'response_type' => 'code', 'state' => 'xyz', 'prompt' => 'login none'],
                'https://example.com/redirect?key=value&state=xyz&error=invalid_request',
            ],
            'Use not supported request parameter'      => [
                ['client_id' => 'tlp-client-id-1', 'redirect_uri' => 'https://example.com/redirect?key=value', 'response_type' => 'code', 'request' => 'notsupported'],
                'https://example.com/redirect?key=value&error=request_not_supported',
            ],
            'Use not supported request URI parameter'  => [
                ['client_id' => 'tlp-client-id-1', 'redirect_uri' => 'https://example.com/redirect?key=value', 'response_type' => 'code', 'request_uri' => 'notsupported'],
                'https://example.com/redirect?key=value&error=request_uri_not_supported',
            ],
            'Use not supported registration parameter' => [
                ['client_id' => 'tlp-client-id-1', 'redirect_uri' => 'https://example.com/redirect?key=value', 'response_type' => 'code', 'registration' => 'notsupported'],
                'https://example.com/redirect?key=value&error=registration_not_supported',
            ],
        ];
    }

    public function testHandleRedirectsWithErrorPKCECodeChallengeExtractionFails(): void
    {
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->user_manager->method('getCurrentUser')->willReturn($user);
        $project = new \Project(['group_id' => 101, 'group_name' => 'Rest Project']);
        $request = (new NullServerRequest())->withQueryParams(
            [
                'client_id'      => 'tlp-client-id-1',
                'redirect_uri'   => 'https://example.com/redirect',
                'response_type'  => 'code',
                'state'          => 'xyz',
                'code_challenge' => 'failure',
                'scope'          => 'scopename:read',
            ]
        );
        $this->app_factory->expects($this->once())->method('getAppMatchingClientId')
            ->willReturn(new OAuth2App(1, 'Jenkins', 'https://example.com/redirect', true, $project));
        $this->scope_extractor->expects($this->once())->method('extractScopes')
            ->willReturn([$this->createMock(AuthenticationScope::class)]);
        $this->pkce_information_extractor->method('extractCodeChallenge')->willThrowException(
            new class extends \RuntimeException implements OAuth2PKCEInformationExtractionException
            {
            }
        );

        $response = HTTPFactoryBuilder::responseFactory()->createResponse(302);
        $this->response_factory->method('createErrorResponse')->willReturn($response);

        self::assertSame($response, $this->controller->handle($request));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderSupportedRequestHTTPMethod')]
    public function testHandleRedirectsWithAuthorizationCodeWhenAPreviousAuthorizationHasBeenGranted(string $request_http_method): void
    {
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->user_manager->method('getCurrentUser')->willReturn($user);
        $project        = ProjectTestBuilder::aProject()->withPublicName('Test Project')->build();
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

        $this->app_factory->expects($this->once())->method('getAppMatchingClientId')
            ->willReturn(new OAuth2App(1, 'Jenkins', 'https://example.com/redirect', true, $project));
        $this->scope_extractor->expects($this->once())->method('extractScopes')
            ->willReturn([OAuth2TestScope::fromItself()]);
        $this->pkce_information_extractor->method('extractCodeChallenge')->willReturn('extracted_code_challenge');
        $this->form_renderer_builder->expects($this->never())->method('buildConsentRequiredResponse');

        $this->consent_checker
            ->expects($this->once())
            ->method('isConsentRequired')
            ->willReturn(false);

        $response = HTTPFactoryBuilder::responseFactory()->createResponse(302);
        $this->response_factory->method('createSuccessfulResponse')->willReturn($response);

        self::assertSame($response, $this->controller->handle($request));
    }

    public function testRendersAuthorizationFormWhenConstentIsRequired(): void
    {
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->user_manager->method('getCurrentUser')->willReturn($user);
        $project = ProjectTestBuilder::aProject()->withPublicName('Test Project')->build();
        $request = (new NullServerRequest())->withQueryParams(
            [
                'client_id'     => 'tlp-client-id-1',
                'redirect_uri'  => 'https://example.com/redirect',
                'response_type' => 'code',
                'state'         => 'xyz',
                'scope'         => 'offline_access',
            ]
        );

        $this->app_factory->expects($this->once())->method('getAppMatchingClientId')
            ->willReturn(new OAuth2App(1, 'Jenkins', 'https://example.com/redirect', true, $project));
        $this->scope_extractor->expects($this->once())->method('extractScopes')
            ->willReturn([OAuth2OfflineAccessScope::fromItself()]);
        $this->pkce_information_extractor->method('extractCodeChallenge')->willReturn('extracted_code_challenge');

        $this->consent_checker
            ->expects($this->once())
            ->method('isConsentRequired')
            ->willReturn(true);


        $this->form_renderer_builder
            ->expects($this->once())
            ->method('buildConsentRequiredResponse')
            ->willReturn(HTTPFactoryBuilder::responseFactory()->createResponse());

        $this->controller->handle($request->withAttribute(BaseLayout::class, LayoutBuilder::build()));
    }

    public function testHandlesRedirectWithAnInteractionRequiredErrorWhenUserNeedsToConsent(): void
    {
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->user_manager->method('getCurrentUser')->willReturn($user);
        $project = ProjectTestBuilder::aProject()->withPublicName('Test Project')->build();
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

        $this->app_factory->method('getAppMatchingClientId')
            ->willReturn(new OAuth2App(1, 'Jenkins', 'https://example.com/redirect', true, $project));
        $this->scope_extractor->expects($this->once())->method('extractScopes')
            ->willReturn([$this->createMock(AuthenticationScope::class)]);
        $this->pkce_information_extractor->method('extractCodeChallenge')->willReturn('extracted_code_challenge');

        $this->consent_checker
            ->expects($this->once())
            ->method('isConsentRequired')
            ->willReturn(true);

        $response = HTTPFactoryBuilder::responseFactory()->createResponse(302);
        $this->response_factory->expects($this->once())->method('createErrorResponse')
            ->with('interaction_required', 'https://example.com/redirect', 'xyz')
            ->willReturn($response);

        self::assertSame($response, $this->controller->handle($request));
    }

    public static function dataProviderSupportedRequestHTTPMethod(): array
    {
        return [
            ['GET'],
            ['POST'],
        ];
    }

    public function testHandleRendersAuthorizationFormWhenMaxAgeParameterIsSetButTheUserHasAuthenticatedRecently(): void
    {
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->user_manager->method('getCurrentUser')->willReturn($user);
        $this->user_manager->method('getUserAccessInfo')->willReturn(
            ['last_auth_success' => (new \DateTimeImmutable())->sub(new \DateInterval('PT60S'))->getTimestamp()]
        );
        $project = ProjectTestBuilder::aProject()->withPublicName('Test Project')->build();
        $request = (new NullServerRequest())->withQueryParams(
            [
                'client_id'     => 'tlp-client-id-1',
                'redirect_uri'  => 'https://example.com/redirect',
                'response_type' => 'code',
                'state'         => 'xyz',
                'scope'         => 'scopename:read',
                'max_age'       => '3600',
            ]
        );
        $this->app_factory->expects($this->once())->method('getAppMatchingClientId')
            ->willReturn(new OAuth2App(1, 'Jenkins', 'https://example.com/redirect', true, $project));
        $this->scope_extractor->expects($this->once())->method('extractScopes')
            ->willReturn([$this->createMock(AuthenticationScope::class)]);
        $this->pkce_information_extractor->method('extractCodeChallenge')->willReturn('extracted_code_challenge');

        $this->form_renderer_builder
            ->expects($this->once())
            ->method('buildConsentRequiredResponse')
            ->willReturn(HTTPFactoryBuilder::responseFactory()->createResponse());

        $this->consent_checker
            ->expects($this->once())
            ->method('isConsentRequired')
            ->willReturn(true);

        $this->controller->handle($request->withAttribute(BaseLayout::class, LayoutBuilder::build()));
    }
}
