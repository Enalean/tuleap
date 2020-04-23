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
 *
 */

declare(strict_types=1);

namespace Tuleap\OpenIDConnectClient\Provider\GenericProvider;

use PHPUnit\Framework\TestCase;

final class GenericProviderTest extends TestCase
{
    public function testReturnsNullJWKSEndpointURLIsEmpty(): void
    {
        $provider = new GenericProvider(
            1,
            'Provider',
            'https://example.com/auth',
            'https://example.com/token',
            '',
            'https://example.com/userinfo',
            'Id Client',
            'secret',
            false,
            'tuleap',
            'fiesta_red'
        );

        $this->assertNull($provider->getJWKSEndpoint());
    }

    public function testReturnsTheJWKSEndpointValueWhenNotEmpty(): void
    {
        $provider = new GenericProvider(
            1,
            'Provider',
            'https://example.com/auth',
            'https://example.com/token',
            'https://example.com/jwks',
            'https://example.com/userinfo',
            'Id Client',
            'secret',
            false,
            'tuleap',
            'fiesta_red'
        );

        $this->assertEquals('https://example.com/jwks', $provider->getJWKSEndpoint());
    }
}
