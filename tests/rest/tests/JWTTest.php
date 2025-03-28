<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\JWT\REST;

use Psr\Http\Message\ResponseInterface;
use REST_TestDataBuilder;
use RestBase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
#[\PHPUnit\Framework\Attributes\Group('TokenTests')]
class JWTTest extends RestBase
{
    public function testGETJWT(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'jwt/'));

        $this->assertGETJWT($response);
    }

    public function testGETJWTWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'jwt/'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETJWT($response);
    }

    private function assertGETJWT(ResponseInterface $response): void
    {
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue(isset($json['token']));
    }
}
