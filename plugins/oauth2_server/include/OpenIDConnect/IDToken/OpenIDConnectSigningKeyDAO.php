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

namespace Tuleap\OAuth2Server\OpenIDConnect\IDToken;

use Tuleap\DB\DataAccessObject;

class OpenIDConnectSigningKeyDAO extends DataAccessObject
{
    public function save(string $public_key, string $encrypted_private_key): void
    {
        $this->getDB()->insert(
            'plugin_oauth2_oidc_signing_key',
            ['public_key' => $public_key, 'private_key' => $encrypted_private_key]
        );
    }

    public function searchPublicKey(): ?string
    {
        $row = $this->getDB()->row('SELECT public_key FROM plugin_oauth2_oidc_signing_key');


        if ($row === null) {
            return null;
        }

        return $row['public_key'];
    }

    public function searchEncryptedPrivateKey(): ?string
    {
        $row = $this->getDB()->row('SELECT private_key FROM plugin_oauth2_oidc_signing_key');


        if ($row === null) {
            return null;
        }

        return $row['private_key'];
    }
}
