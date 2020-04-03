<?php
/**
 * Copyright (c) Enalean, 2016 - present. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Provider\GenericProvider;

use ParagonIE\EasyDB\EasyDB;
use Tuleap\DB\DataAccessObject;

class GenericProviderDao extends DataAccessObject
{
    public function create(
        $name,
        $authorization_endpoint,
        $token_endpoint,
        $user_info_endpoint,
        $client_id,
        $client_secret,
        $icon,
        $color
    ): int {
        return $this->getDB()->tryFlatTransaction(
            function (EasyDB $db) use (
                $name,
                $authorization_endpoint,
                $token_endpoint,
                $user_info_endpoint,
                $client_id,
                $client_secret,
                $icon,
                $color
            ): int {
                $sql = "INSERT INTO plugin_openidconnectclient_provider(name, client_id, client_secret, icon, color)
                    VALUES (?, ?, ?, ?, ?)";

                $db->run($sql, $name, $client_id, $client_secret, $icon, $color);

                $id = $db->lastInsertId();

                $sql = "INSERT INTO plugin_openidconnectclient_provider_generic(provider_id, authorization_endpoint, token_endpoint, user_info_endpoint)
                    VALUES (?, ?, ?, ?)";

                $db->run($sql, $id, $authorization_endpoint, $token_endpoint, $user_info_endpoint);

                return (int) $id;
            }
        );
    }

    private function disableUniqueAuthenticationProvider(): void
    {
        $sql = "UPDATE plugin_openidconnectclient_provider SET unique_authentication_endpoint = FALSE";
        $this->getDB()->run($sql);
    }

    public function save(
        $id,
        $name,
        $authorization_endpoint,
        $token_endpoint,
        $user_info_endpoint,
        $is_unique_authentication_endpoint,
        $client_id,
        $client_secret,
        $icon,
        $color
    ): void {
        $this->getDB()->tryFlatTransaction(
            function (EasyDB $db) use (
                $id,
                $name,
                $authorization_endpoint,
                $token_endpoint,
                $user_info_endpoint,
                $is_unique_authentication_endpoint,
                $client_id,
                $client_secret,
                $icon,
                $color
            ): void {
                if ($is_unique_authentication_endpoint) {
                    $this->disableUniqueAuthenticationProvider();
                }

                $sql = "UPDATE plugin_openidconnectclient_provider SET
                        name = ?,
                        client_id = ?,
                        client_secret = ?,
                        unique_authentication_endpoint = ?,
                        icon = ?,
                        color = ?
                    WHERE id = ?";
                $db->run($sql, $name, $client_id, $client_secret, $is_unique_authentication_endpoint, $icon, $color, $id);

                $sql = "UPDATE plugin_openidconnectclient_provider_generic SET
                        authorization_endpoint = ?,
                        token_endpoint = ?,
                        user_info_endpoint = ?
                    WHERE provider_id = ?";

                $db->run($sql, $authorization_endpoint, $token_endpoint, $user_info_endpoint, $id);
            }
        );
    }
}
