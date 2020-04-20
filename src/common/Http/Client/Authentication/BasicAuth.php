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

use Psr\Http\Message\RequestInterface;
use Tuleap\Cryptography\ConcealedString;

final class BasicAuth
{
    public function authenticate(RequestInterface $request, string $username, ConcealedString $password): RequestInterface
    {
        return $request->withHeader(
            'Authorization',
            sprintf(
                'Basic %s',
                sodium_bin2base64(
                    sprintf('%s:%s', $username, $password->getString()),
                    SODIUM_BASE64_VARIANT_ORIGINAL
                )
            )
        );
    }
}
