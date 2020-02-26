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

namespace Tuleap\Http\Server\Authentication;

use Psr\Http\Message\ServerRequestInterface;
use Tuleap\Cryptography\ConcealedString;

final class BasicAuthLoginExtractor
{
    public function extract(ServerRequestInterface $server_request): ?LoginCredentialSet
    {
        $authorization_header = $server_request->getHeaderLine('Authorization');
        $match_success        = preg_match("/Basic\s+(.*)$/i", $authorization_header, $matches);
        if ($match_success !== 1) {
            return null;
        }
        $base64_encoded_basic_auth_credential = $matches[1];
        $basic_auth_credential                = explode(':', base64_decode($base64_encoded_basic_auth_credential), 2);
        if (count($basic_auth_credential) !== 2) {
            return null;
        }

        [$basic_auth_username, $basic_auth_password] = $basic_auth_credential;
        return new LoginCredentialSet($basic_auth_username, new ConcealedString($basic_auth_password));
    }
}
