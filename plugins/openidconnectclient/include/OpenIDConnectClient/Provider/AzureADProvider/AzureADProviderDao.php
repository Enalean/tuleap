<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Provider\AzureADProvider;

use ParagonIE\EasyDB\EasyDB;
use Tuleap\DB\DataAccessObject;

class AzureADProviderDao extends DataAccessObject
{
    public function create(
        string $name,
        string $client_id,
        string $client_secret,
        string $icon,
        string $color,
        string $tenant_id,
        string $acceptable_tenant_auth_identifier
    ): int {
        return $this->getDB()->tryFlatTransaction(
            static function (EasyDB $db) use (
                $name,
                $client_id,
                $client_secret,
                $icon,
                $color,
                $tenant_id,
                $acceptable_tenant_auth_identifier
            ): int {
                $sql = "INSERT INTO plugin_openidconnectclient_provider(name, client_id, client_secret, icon, color)
                    VALUES (?, ?, ?, ?, ?)";

                $db->run($sql, $name, $client_id, $client_secret, $icon, $color);

                $id = $db->lastInsertId();

                $sql = 'INSERT INTO plugin_openidconnectclient_provider_azure_ad(provider_id, tenant_id, acceptable_tenant_auth_identifier)
                    VALUES (?, ?, ?)';
                $db->run($sql, $id, $tenant_id, $acceptable_tenant_auth_identifier);

                return (int) $id;
            }
        );
    }

    private function disableUniqueAuthenticationProvider(): bool
    {
        $sql = "UPDATE plugin_openidconnectclient_provider SET unique_authentication_endpoint = FALSE";
        return $this->getDB()->run($sql);
    }

    public function save(
        int $id,
        string $name,
        bool $is_unique_authentication_endpoint,
        string $client_id,
        string $client_secret,
        string $icon,
        string $color,
        string $tenant_id,
        string $acceptable_tenant_auth_identifier
    ): void {
        $this->getDB()->tryFlatTransaction(
            function (EasyDB $db) use (
                $id,
                $name,
                $is_unique_authentication_endpoint,
                $client_id,
                $client_secret,
                $icon,
                $color,
                $tenant_id,
                $acceptable_tenant_auth_identifier
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

                $sql = "UPDATE plugin_openidconnectclient_provider_azure_ad SET
                        tenant_id = ?,
                        acceptable_tenant_auth_identifier = ?
                    WHERE provider_id = ?";

                $db->run($sql, $tenant_id, $acceptable_tenant_auth_identifier, $id);
            }
        );
    }
}
