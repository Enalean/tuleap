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

namespace Tuleap\User\OAuth2;

use Tuleap\Cryptography\ConcealedString;

/**
 * @see https://tools.ietf.org/html/rfc6750#section-2.1
 */
final class BearerTokenHeaderParser
{
    /**
     * @psalm-pure
     */
    public function parseHeaderLine(string $header_line): ?ConcealedString
    {
        $matches = [];
        preg_match('/^\s*Bearer\s(?<token>(?:[a-zA-Z0-9]|-|\.|\_|\~|\+|\/|=)+)$/', $header_line, $matches);

        if (! isset($matches['token'])) {
            return null;
        }

        return new ConcealedString($matches['token']);
    }
}
