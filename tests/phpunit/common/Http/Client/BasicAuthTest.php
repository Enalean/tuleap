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

namespace Tuleap\Http\Client\Authentication;

use PHPUnit\Framework\TestCase;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Http\HTTPFactoryBuilder;

final class BasicAuthTest extends TestCase
{
    /**
     * @see RFC7617 section 2 (test vector included) https://tools.ietf.org/html/rfc7617#section-2
     */
    public function testAddsAuthorizationToAuthenticateTheRequest(): void
    {
        $basic_auth = new BasicAuth();

        $request = $basic_auth->authenticate(
            HTTPFactoryBuilder::requestFactory()->createRequest('GET', '/'),
            'Aladdin',
            new ConcealedString('open sesame')
        );

        $this->assertEquals('Basic QWxhZGRpbjpvcGVuIHNlc2FtZQ==', $request->getHeaderLine('Authorization'));
    }
}
