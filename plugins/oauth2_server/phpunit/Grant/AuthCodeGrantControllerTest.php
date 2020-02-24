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

namespace Tuleap\OAuth2Server\Grant;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\OAuth2Server\AccessToken\OAuth2AccessTokenWithIdentifier;

final class AuthCodeGrantControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|AuthorizationCodeGrantResponseBuilder
     */
    private $response_builder;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\UserManager
     */
    private $user_manager;
    /**
     * @var AuthCodeGrantController
     */
    private $controller;

    protected function setUp(): void
    {
        $this->response_builder = \Mockery::mock(AuthorizationCodeGrantResponseBuilder::class);
        $this->user_manager     = \Mockery::mock(\UserManager::class);

        $this->controller = new AuthCodeGrantController(
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory(),
            $this->response_builder,
            $this->user_manager,
            \Mockery::mock(EmitterInterface::class)
        );
    }

    public function testBuildsHTTPResponse(): void
    {
        $this->user_manager->shouldReceive('getUserByUserName')->andReturn(new \PFUser(['language_id' => 'en']));
        $this->response_builder->shouldReceive('buildResponse')->andReturn(
            OAuth2AccessTokenSuccessfulRequestRepresentation::fromAccessToken(
                new OAuth2AccessTokenWithIdentifier(new ConcealedString('identifier'), new \DateTimeImmutable('@20')),
                new \DateTimeImmutable('@10')
            )
        );

        $request = \Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getParsedBody')->andReturn(['grant_type' => 'authorization_code']);

        $response = $this->controller->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json;charset=UTF-8', $response->getHeaderLine('Content-Type'));
    }

    public function testRejectsRequestThatDoesNotHaveAnExplicitGrantType(): void
    {
        $request = \Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getParsedBody')->andReturn(null);

        $this->response_builder->shouldNotReceive('buildResponse');
        $response = $this->controller->handle($request);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/json;charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertJsonStringEqualsJsonString('{"error":"invalid_request"}', $response->getBody()->getContents());
    }

    public function testRejectsRequestWithAnUnsupportedGrantType(): void
    {
        $request = \Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getParsedBody')->andReturn(['grant_type' => 'password']);

        $this->response_builder->shouldNotReceive('buildResponse');
        $response = $this->controller->handle($request);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/json;charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertJsonStringEqualsJsonString('{"error":"invalid_grant"}', $response->getBody()->getContents());
    }
}
