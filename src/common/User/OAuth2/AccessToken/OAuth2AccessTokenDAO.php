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

namespace Tuleap\User\OAuth2\AccessToken;

use Tuleap\DB\DataAccessObject;

class OAuth2AccessTokenDAO extends DataAccessObject
{
    public function create(int $user_id, string $hashed_verification_string, int $expiration_date_timestamp): int
    {
        return (int) $this->getDB()->insertReturnId(
            'oauth2_access_token',
            [
                'user_id'         => $user_id,
                'verifier'        => $hashed_verification_string,
                'expiration_date' => $expiration_date_timestamp
            ]
        );
    }

    /**
     * @psalm-return null|array{verifier:string,user_id:int,expiration_date:int}
     */
    public function searchAccessToken(int $access_token_id): ?array
    {
        return $this->getDB()->row('SELECT verifier, user_id, expiration_date FROM oauth2_access_token WHERE id = ?', $access_token_id);
    }
}
