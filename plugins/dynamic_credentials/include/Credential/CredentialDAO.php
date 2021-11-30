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

namespace Tuleap\DynamicCredentials\Credential;

use Tuleap\DB\DataAccessObject;

class CredentialDAO extends DataAccessObject
{
    public function save($identifier, $password, $expiration_date)
    {
        $this->getDB()->insert(
            'plugin_dynamic_credentials_account',
            [
                'identifier' => $identifier,
                'password'   => $password,
                'expiration' => $expiration_date,
            ]
        );
    }

    /**
     * @return int
     */
    public function revokeByIdentifier($identifier)
    {
        return $this->getDB()->update(
            'plugin_dynamic_credentials_account',
            ['revoked' => 1],
            ['identifier' => $identifier]
        );
    }

    /**
     * @return array
     */
    public function getUnrevokedCredentialByIdentifier($identifier)
    {
        return $this->getDB()->row(
            'SELECT * FROM plugin_dynamic_credentials_account WHERE revoked = 0 AND identifier = ?',
            $identifier
        );
    }

    public function deleteByExpirationDate($expiration_date)
    {
        $this->getDB()->run('DELETE FROM plugin_dynamic_credentials_account WHERE ? >= expiration', $expiration_date);
    }
}
