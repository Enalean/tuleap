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

namespace Tuleap\OAuth2Server\User;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\OAuth2Server\OAuth2TestScope;
use Tuleap\OAuth2Server\OpenIDConnect\Scope\OpenIDConnectEmailScope;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\User\OAuth2\ResourceServer\GrantedAuthorization;
use Tuleap\User\OAuth2\ResourceServer\OAuth2ResourceServerMiddleware;

final class UserInfoControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var UserInfoController
     */
    private $controller;

    protected function setUp(): void
    {
        $this->controller = new UserInfoController(
            new JSONResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory()),
            M::mock(EmitterInterface::class)
        );
    }

    public function testReturnsAJSONObjectContainingSubjectClaim(): void
    {
        $granted_authorization = new GrantedAuthorization(
            UserTestBuilder::aUser()->withId(110)->build(),
            [OAuth2TestScope::fromItself()]
        );

        $request = (new NullServerRequest())->withAttribute(
            OAuth2ResourceServerMiddleware::class,
            $granted_authorization
        );

        $response = $this->controller->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"sub":"110"}', $response->getBody()->getContents());
    }

    public function testReturnsAJSONObjectContainingEmailClaim(): void
    {
        $user                  = UserTestBuilder::aUser()->withId(110)
            ->withEmail('user@example.com')
            ->withStatus(\PFUser::STATUS_ACTIVE)
            ->build();
        $granted_authorization = new GrantedAuthorization($user, [OpenIDConnectEmailScope::fromItself()]);
        $request               = (new NullServerRequest())->withAttribute(
            OAuth2ResourceServerMiddleware::class,
            $granted_authorization
        );

        $response = $this->controller->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            '{"sub":"110","email":"user@example.com","email_verified":true}',
            $response->getBody()->getContents()
        );
    }
}
