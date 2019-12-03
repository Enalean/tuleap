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

use Tuleap\DB\DataAccessObject;

class ProviderDao extends DataAccessObject
{
    public function searchById($provider_id)
    {
        $sql = "SELECT generic_provider.*, azure_provider.*,
                       provider.id,
                       provider.name,
                       provider.client_id,
                       provider.client_secret,
                       provider.unique_authentication_endpoint,
                       provider.icon,
                       provider.color
                FROM plugin_openidconnectclient_provider as provider
                LEFT JOIN plugin_openidconnectclient_provider_generic AS generic_provider
                   ON generic_provider.provider_id = provider.id
                LEFT JOIN plugin_openidconnectclient_provider_azure_ad AS azure_provider
                   ON azure_provider.provider_id = provider.id
                WHERE provider.id = ?";

        return $this->getDB()->row($sql, $provider_id);
    }

    public function deleteById($id)
    {
        $sql = "DELETE generic_provider, plugin_openidconnectclient_provider, azure_provider
                FROM plugin_openidconnectclient_provider
                LEFT JOIN plugin_openidconnectclient_provider_generic AS generic_provider
                    ON plugin_openidconnectclient_provider.id =  generic_provider.provider_id
                LEFT JOIN plugin_openidconnectclient_provider_azure_ad AS azure_provider
                    ON plugin_openidconnectclient_provider.id =  azure_provider.provider_id
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
        $sql = "SELECT generic_provider.*, azure_provider.*,
                       provider.id,
                       provider.name,
                       provider.client_id,
                       provider.client_secret,
                       provider.unique_authentication_endpoint,
                       provider.icon,
                       provider.color
                FROM plugin_openidconnectclient_provider as provider
                LEFT JOIN plugin_openidconnectclient_provider_generic AS generic_provider
                   ON generic_provider.provider_id = provider.id
                LEFT JOIN plugin_openidconnectclient_provider_azure_ad AS azure_provider
                   ON azure_provider.provider_id = provider.id
                WHERE client_id != ''
                    AND client_secret != ''
                ORDER BY unique_authentication_endpoint DESC, name ASC";

        if ($this->isAProviderConfiguredAsUniqueEndPointProvider()) {
            $sql = "SELECT generic_provider.*, azure_provider.*,
                           provider.id,
                           provider.name,
                           provider.client_id,
                           provider.client_secret,
                           provider.unique_authentication_endpoint,
                           provider.icon,
                           provider.color
                    FROM plugin_openidconnectclient_provider as provider
                    LEFT JOIN plugin_openidconnectclient_provider_generic AS generic_provider
                        ON generic_provider.provider_id = provider.id
                    LEFT JOIN plugin_openidconnectclient_provider_azure_ad AS azure_provider
                        ON azure_provider.provider_id = provider.id
                    WHERE client_id != '' AND client_secret != '' AND unique_authentication_endpoint = TRUE

                    ORDER BY unique_authentication_endpoint DESC, name ASC";
        }

        return $this->getDB()->run($sql);
    }

    public function searchProviders()
    {
        $sql = "SELECT generic_provider.*, azure_provider.*,
                           provider.id,
                           provider.name,
                           provider.client_id,
                           provider.client_secret,
                           provider.unique_authentication_endpoint,
                           provider.icon,
                           provider.color
                FROM plugin_openidconnectclient_provider as provider
                LEFT JOIN plugin_openidconnectclient_provider_generic AS generic_provider
                    ON generic_provider.provider_id = provider.id
                LEFT JOIN plugin_openidconnectclient_provider_azure_ad AS azure_provider
                    ON azure_provider.provider_id = provider.id
                ORDER BY unique_authentication_endpoint DESC, name ASC";

        return $this->getDB()->run($sql);
    }
}
