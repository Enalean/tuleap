<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use DataAccessObject;

class ProviderDao  extends DataAccessObject {

    public function searchById($provider_id) {
        $provider_id = $this->getDa()->escapeInt($provider_id);
        $sql         = "SELECT * FROM plugin_openidconnectclient_provider WHERE id = $provider_id";
        return $this->retrieveFirstRow($sql);
    }

    public function create(
        $name,
        $authorization_endpoint,
        $token_endpoint,
        $user_info_endpoint,
        $client_id,
        $client_secret,
        $icon,
        $color
    ) {
        $name                   = $this->getDa()->quoteSmart($name);
        $authorization_endpoint = $this->getDa()->quoteSmart($authorization_endpoint);
        $token_endpoint         = $this->getDa()->quoteSmart($token_endpoint);
        $user_info_endpoint     = $this->getDa()->quoteSmart($user_info_endpoint);
        $client_id              = $this->getDa()->quoteSmart($client_id);
        $client_secret          = $this->getDa()->quoteSmart($client_secret);
        $icon                   = $this->getDa()->quoteSmart($icon);
        $color                  = $this->getDa()->quoteSmart($color);

        $sql = "INSERT INTO plugin_openidconnectclient_provider(
                    name, authorization_endpoint, token_endpoint, user_info_endpoint, client_id, client_secret, icon, color
                ) VALUES (
                    $name, $authorization_endpoint, $token_endpoint, $user_info_endpoint, $client_id, $client_secret, $icon, $color
                );";
        return $this->updateAndGetLastId($sql);
    }

    public function save(
        $id,
        $name,
        $authorization_endpoint,
        $token_endpoint,
        $user_info_endpoint,
        $client_id,
        $client_secret,
        $icon,
        $color
    ) {
        $id                     = $this->getDa()->escapeInt($id);
        $name                   = $this->getDa()->quoteSmart($name);
        $authorization_endpoint = $this->getDa()->quoteSmart($authorization_endpoint);
        $token_endpoint         = $this->getDa()->quoteSmart($token_endpoint);
        $user_info_endpoint     = $this->getDa()->quoteSmart($user_info_endpoint);
        $client_id              = $this->getDa()->quoteSmart($client_id);
        $client_secret          = $this->getDa()->quoteSmart($client_secret);
        $icon                   = $this->getDa()->quoteSmart($icon);
        $color                  = $this->getDa()->quoteSmart($color);

        $sql = "UPDATE plugin_openidconnectclient_provider SET
                  name = $name, authorization_endpoint = $authorization_endpoint,
                  token_endpoint = $token_endpoint,
                  user_info_endpoint = $user_info_endpoint,
                  client_id = $client_id,
                  client_secret = $client_secret,
                  icon = $icon,
                  color = $color
                WHERE id = $id";

        return $this->update($sql);
    }

    public function deleteById($id) {
        $id  = $this->getDa()->escapeInt($id);
        $sql = "DELETE FROM plugin_openidconnectclient_provider WHERE id = $id";
        return $this->update($sql);
    }

    private function isAProviderConfiguredAsUniqueEndPointProvider()
    {
        $sql = "SELECT * FROM plugin_openidconnectclient_provider WHERE unique_authentication_endpoint = TRUE LIMIT 1";
        $this->retrieve($sql);
        return $this->foundRows() > 0;
    }

    public function searchProvidersUsableToLogIn() {
        $sql = "SELECT *
                FROM plugin_openidconnectclient_provider
                WHERE client_id != '' AND client_secret != ''";
        if ($this->isAProviderConfiguredAsUniqueEndPointProvider()) {
            $sql = "SELECT *
                    FROM plugin_openidconnectclient_provider
                    WHERE client_id != '' AND client_secret != '' AND unique_authentication_endpoint = TRUE";
        }
        return $this->retrieve($sql);
    }

    public function searchProviders() {
        $sql = "SELECT * FROM plugin_openidconnectclient_provider";
        return $this->retrieve($sql);
    }
}
