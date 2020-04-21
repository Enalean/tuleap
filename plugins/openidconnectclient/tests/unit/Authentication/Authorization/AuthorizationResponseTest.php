<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Authentication\Authorization;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class AuthorizationResponseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testResponseIsBuiltWhenAllParametersAreAvailable()
    {
        $request = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('get')->with('code')->andReturns('code');
        $request->shouldReceive('get')->with('state')->andReturns('state');

        $response = AuthorizationResponse::buildFromHTTPRequest($request);
        $this->assertSame('code', $response->getCode());
        $this->assertSame('state', $response->getState());
    }

    public function testResponseConstructionIsRejectedWhenCodeIsMissing()
    {
        $request = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('get')->with('code')->andReturns(false);

        $this->expectException(MissingParameterAuthorizationResponseException::class);
        $this->expectExceptionMessage('code');

        AuthorizationResponse::buildFromHTTPRequest($request);
    }

    public function testResponseConstructionIsRejectedWhenStateIsMissing()
    {
        $request = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('get')->with('code')->andReturns('code');
        $request->shouldReceive('get')->with('state')->andReturns(false);

        $this->expectException(MissingParameterAuthorizationResponseException::class);
        $this->expectExceptionMessage('state');

        AuthorizationResponse::buildFromHTTPRequest($request);
    }
}
