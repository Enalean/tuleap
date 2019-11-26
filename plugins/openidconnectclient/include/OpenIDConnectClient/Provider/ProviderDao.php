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

namespace Tuleap\OpenIDConnectClient\Provider;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;

class ProviderDao extends DataAccessObject
{
    public function searchById($provider_id)
    {
        $sql = "SELECT *
                FROM plugin_openidconnectclient_provider
                INNER JOIN plugin_openidconnectclient_provider_generic as generic_openid
                     ON generic_openid.provider_id = plugin_openidconnectclient_provider.id
                WHERE id = ?";

        return $this->getDB()->row($sql, $provider_id);
    }

    public function deleteById($id)
    {
        $sql = "DELETE generic_provider, plugin_openidconnectclient_provider
                FROM plugin_openidconnectclient_provider
                JOIN plugin_openidconnectclient_provider_generic AS generic_provider
                    ON plugin_openidconnectclient_provider.id =  generic_provider.provider_id
                WHERE plugin_openidconnectclient_provider.id = ?";
        return $this->getDB()->run($sql, $id);
    }

    public function isAProviderConfiguredAsUniqueEndPointProvider()
    {
        $sql = "SELECT * FROM plugin_openidconnectclient_provider WHERE unique_authentication_endpoint = TRUE LIMIT 1";
        $this->getDB()->run($sql);

        return $this->foundRows() > 0;
    }

    public function searchProvidersUsableToLogIn()
    {
        $sql = "SELECT *
                FROM plugin_openidconnectclient_provider
                INNER JOIN plugin_openidconnectclient_provider_generic AS generic_provider
                    ON plugin_openidconnectclient_provider.id = generic_provider.provider_id
                WHERE client_id != '' AND client_secret != ''
                ORDER BY unique_authentication_endpoint DESC, name ASC";

        if ($this->isAProviderConfiguredAsUniqueEndPointProvider()) {
            $sql = "SELECT *
                    FROM plugin_openidconnectclient_provider
                    INNER JOIN plugin_openidconnectclient_provider_generic generic_provider
                        ON plugin_openidconnectclient_provider.id = generic_provider.provider_id
                    WHERE client_id != '' AND client_secret != '' AND unique_authentication_endpoint = TRUE

                    ORDER BY unique_authentication_endpoint DESC, name ASC";
        }

        return $this->getDB()->run($sql);
    }

    public function searchProviders()
    {
        $sql = "SELECT *
                FROM plugin_openidconnectclient_provider
                INNER JOIN plugin_openidconnectclient_provider_generic AS generic_provider
                    ON plugin_openidconnectclient_provider.id = generic_provider.provider_id
                ORDER BY unique_authentication_endpoint DESC, name ASC";

        return $this->getDB()->run($sql);
    }
}
