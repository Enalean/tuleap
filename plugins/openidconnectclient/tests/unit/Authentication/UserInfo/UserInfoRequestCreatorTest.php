<?php
/**
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Authentication\UserInfo;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestFactoryInterface;
use Tuleap\OpenIDConnectClient\Authentication\Token\TokenResponse;
use Tuleap\OpenIDConnectClient\Provider\Provider;

class UserInfoRequestCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testAnEmptyRequestIsCreatedWhenNoUserEndpointIsAvailable()
    {
        $request_factory           = \Mockery::mock(RequestFactoryInterface::class);
        $user_info_request_creator = new UserInfoRequestCreator($request_factory);

        $provider = \Mockery::mock(Provider::class);
        $provider->shouldReceive('getUserInfoEndpoint')->andReturns('');

        $user_info_request = $user_info_request_creator->createUserInfoRequest(
            $provider,
            \Mockery::mock(TokenResponse::class)
        );
        $this->assertInstanceOf(EmptyUserInfoRequest::class, $user_info_request);
    }
}
