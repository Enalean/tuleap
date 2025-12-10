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

namespace Tuleap\OAuth2ServerCore\OpenIDConnect;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Symmetric\EncryptionAdditionalData;
use Tuleap\DB\DataAccessObject;

class OpenIDConnectSigningKeyDAO extends DataAccessObject
{
    /**
     * @param non-empty-string $public_key
     */
    public function save(string $public_key, ConcealedString $private_key, int $expiration_date, int $cleanup_keys_date): void
    {
        $this->getDB()->insert(
            'oauth2_server_oidc_signing_key',
            [
                'public_key' => $public_key,
                'private_key' => $this->encryptDataToStoreInATableRow($private_key, $this->getPrivateKeyEncryptionAdditionalData($public_key)),
                'expiration_date' => $expiration_date,
            ]
        );
        $this->getDB()->run('DELETE FROM oauth2_server_oidc_signing_key WHERE ? > expiration_date', $cleanup_keys_date);
    }

    /**
     * @return string[]
     */
    public function searchPublicKeys(): array
    {
        return $this->getDB()->column('SELECT public_key FROM oauth2_server_oidc_signing_key');
    }

    /**
     * @return string[]|null
     * @psalm-return array{public_key:non-empty-string,private_key:ConcealedString}|null
     */
    public function searchMostRecentNonExpiredPrivateKey(int $current_time): ?array
    {
        $row = $this->getDB()->row(
            'SELECT public_key, private_key, expiration_date
                       FROM oauth2_server_oidc_signing_key
                       WHERE expiration_date >= ?
                       ORDER BY expiration_date DESC
                       LIMIT 1',
            $current_time
        );

        if ($row === null) {
            return null;
        }

        return [
            'public_key' => $row['public_key'],
            'private_key' => $this->decryptDataStoredInATableRow(
                $row['private_key'],
                $this->getPrivateKeyEncryptionAdditionalData($row['public_key']),
            ),
        ];
    }

    /**
     * @param non-empty-string $public_key
     */
    private function getPrivateKeyEncryptionAdditionalData(string $public_key): EncryptionAdditionalData
    {
        return new EncryptionAdditionalData(
            'oauth2_server_oidc_signing_key',
            'private_key',
            $public_key,
        );
    }
}
