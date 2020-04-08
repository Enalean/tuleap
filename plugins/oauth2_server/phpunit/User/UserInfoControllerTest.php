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
use Psr\Http\Message\ServerRequestInterface;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class UserInfoControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testReturnsAJSONObjectContainingSubjectClaim(): void
    {
        $user_manager = M::mock(\UserManager::class);
        $controller   = new UserInfoController(
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory(),
            $user_manager,
            M::mock(EmitterInterface::class)
        );
        $user_manager->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn(UserTestBuilder::aUser()->withId(110)->build());

        $response = $controller->handle(M::mock(ServerRequestInterface::class));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"sub":"110"}', $response->getBody()->getContents());
    }
}
