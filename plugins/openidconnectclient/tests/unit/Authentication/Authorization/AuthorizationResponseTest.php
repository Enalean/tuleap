<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Authentication\Authorization;

use Tuleap\Test\Builders\HTTPRequestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AuthorizationResponseTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testResponseIsBuiltWhenAllParametersAreAvailable(): void
    {
        $request = HTTPRequestBuilder::get()->withParams(['code' => 'code', 'state' => 'state'])->build();

        $response = AuthorizationResponse::buildFromHTTPRequest($request);
        self::assertSame('code', $response->getCode());
        self::assertSame('state', $response->getState());
    }

    public function testResponseConstructionIsRejectedWhenCodeIsMissing(): void
    {
        $request = HTTPRequestBuilder::get()->build();

        $this->expectException(MissingParameterAuthorizationResponseException::class);
        $this->expectExceptionMessage('code');

        AuthorizationResponse::buildFromHTTPRequest($request);
    }

    public function testResponseConstructionIsRejectedWhenStateIsMissing(): void
    {
        $request = HTTPRequestBuilder::get()->withParams(['code' => 'code'])->build();

        $this->expectException(MissingParameterAuthorizationResponseException::class);
        $this->expectExceptionMessage('state');

        AuthorizationResponse::buildFromHTTPRequest($request);
    }
}
