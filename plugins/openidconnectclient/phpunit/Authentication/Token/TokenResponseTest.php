<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Authentication\Token;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class TokenResponseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testNotValidJSONISRejected()
    {
        $http_response = \Mockery::mock(ResponseInterface::class);
        $http_response->shouldReceive('getBody')->andReturns('{NotJSONValid');

        $this->expectException(IncorrectlyFormattedTokenResponseException::class);

        TokenResponse::buildFromHTTPResponse($http_response);
    }

    public function testJSONWithMissingEntryIsRejected()
    {
        $http_response = \Mockery::mock(ResponseInterface::class);
        $http_response->shouldReceive('getBody')->andReturns(json_encode(['id_token' => 'token']));

        $this->expectException(IncorrectlyFormattedTokenResponseException::class);
        $this->expectExceptionMessageMatches('{"id_token":"token"}');

        TokenResponse::buildFromHTTPResponse($http_response);
    }

    public function testInvalidTokenTypeIsRejected()
    {
        $http_response = \Mockery::mock(ResponseInterface::class);
        $http_response->shouldReceive('getBody')->andReturns(
            json_encode(
                [
                    'id_token'     => 'token',
                    'access_token' => 'access',
                    'token_type'   => 'MAC',
                ]
            )
        );

        $this->expectException(IncorrectTokenResponseTypeException::class);

        TokenResponse::buildFromHTTPResponse($http_response);
    }

    public function testResponseTokenIsParsed()
    {
        $http_response = \Mockery::mock(ResponseInterface::class);
        $http_response->shouldReceive('getBody')->andReturns(
            json_encode(
                [
                    'id_token'     => 'token',
                    'access_token' => 'access',
                    'token_type'   => 'bearer',
                ]
            )
        );

        $token_response = TokenResponse::buildFromHTTPResponse($http_response);
        $this->assertSame($token_response->getAccessToken(), 'access');
        $this->assertSame($token_response->getIDToken(), 'token');
    }
}
