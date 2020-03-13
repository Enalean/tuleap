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
use Tuleap\OAuth2Server\User\AuthorizationCreator;
use Tuleap\OAuth2Server\User\NewAuthorization;
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
     * @var M\LegacyMockInterface|M\MockInterface|AuthorizationCreator
     */
    private $authorization_creator;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|AuthorizationCodeResponseFactory
     */
    private $response_factory;
    /**
     * @var \CSRFSynchronizerToken|M\LegacyMockInterface|M\MockInterface
     */
    private $csrf_token;

    protected function setUp(): void
    {
        $this->user_manager          = M::mock(\UserManager::class);
        $this->authorization_creator = M::mock(AuthorizationCreator::class);
        $this->response_factory      = M::mock(AuthorizationCodeResponseFactory::class);
        $this->csrf_token            = M::mock(\CSRFSynchronizerToken::class);
        $this->controller            = new AuthorizationEndpointPostController(
            $this->user_manager,
            $this->authorization_creator,
            $this->response_factory,
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

    public function dataProviderInvalidBodyParams(): array
    {
        return [
            'No redirect URI'                 => [['state' => 'xyz']],
            'Redirect URI is not a string'    => [['redirect_uri' => false]],
            'No App ID'                       => [['redirect_uri' => 'https://example.com']],
            'App ID cannot be cast to int'    => [['redirect_uri' => 'https://example.com', 'app_id' => 'invalid']],
            'No scopes'                       => [['redirect_uri' => 'https://example.com', 'app_id' => '13']],
            'Scopes are not array of strings' => [['redirect_uri' => 'https://example.com', 'app_id' => '13', 'scope' => [false]]]
        ];
    }

    /**
     * @dataProvider dataProviderInvalidBodyParams
     */
    public function testHandleThrowsForbiddenWhenInvalidBodyParams(array $body_params): void
    {
        $this->user_manager->shouldReceive('getCurrentUser')
            ->andReturn(UserTestBuilder::aUser()->withId(102)->build());
        $request = (new NullServerRequest())->withParsedBody($body_params);

        $this->expectException(ForbiddenException::class);
        $this->controller->handle($request);
    }

    public function testHandleRedirects(): void
    {
        $user = UserTestBuilder::aUser()->withId(102)->build();
        $this->user_manager->shouldReceive('getCurrentUser')
            ->andReturn($user);
        $request = (new NullServerRequest())->withParsedBody(
            ['redirect_uri' => 'https://example.com', 'app_id' => '77', 'scope' => ['foo:bar', 'type:value']]
        );
        $this->csrf_token->shouldReceive('check')->once();
        $response = HTTPFactoryBuilder::responseFactory()->createResponse(302);
        $this->response_factory->shouldReceive('createSuccessfulResponse')->once()->andReturn($response);
        $this->authorization_creator->shouldReceive('saveAuthorization')
            ->once()
            ->with(
                M::on(
                    function (NewAuthorization $new_authorization) use ($user) {
                        $identifiers = $new_authorization->getScopeIdentifiers();
                        return $new_authorization->getAppId() === 77
                            && $new_authorization->getUser() === $user
                            && $identifiers[0]->toString() === 'foo:bar'
                            && $identifiers[1]->toString() === 'type:value';
                    }
                )
            );

        $this->assertSame($response, $this->controller->handle($request));
    }
}
