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

namespace Tuleap\OpenIDConnectClient\UserMapping;

use DataAccessObject;

class UserMappingDao extends DataAccessObject
{

    public function save($user_id, $provider_id, $identifier, $last_used)
    {
        $user_id     = $this->getDa()->escapeInt($user_id);
        $provider_id = $this->getDa()->escapeInt($provider_id);
        $identifier  = $this->getDa()->quoteSmart($identifier);
        $last_used   = $this->getDa()->escapeInt($last_used);

        $sql = "INSERT INTO plugin_openidconnectclient_user_mapping(user_id, provider_id, user_openidconnect_identifier, last_used)
                VALUES($user_id, $provider_id, $identifier, $last_used)";
        return $this->update($sql);
    }

    public function deleteById($id)
    {
        $id = $this->getDa()->escapeInt($id);

        $sql = "DELETE FROM plugin_openidconnectclient_user_mapping
                WHERE id = $id";
        return $this->update($sql);
    }

    public function updateLastUsed($id, $last_used)
    {
        $id        = $this->getDa()->escapeInt($id);
        $last_used = $this->getDa()->escapeInt($last_used);

        $sql = "UPDATE plugin_openidconnectclient_user_mapping SET last_used = $last_used
                WHERE id = $id";
        return $this->update($sql);
    }

    public function searchById($id)
    {
        $id = $this->getDa()->escapeInt($id);

        $sql = "SELECT * FROM plugin_openidconnectclient_user_mapping
                WHERE id = $id";
        return $this->retrieve($sql);
    }

    public function searchByIdentifierAndProviderId($identifier, $provider_id)
    {
        $identifier  = $this->getDa()->quoteSmart($identifier);
        $provider_id = $this->getDa()->escapeInt($provider_id);

        $sql = "SELECT * FROM plugin_openidconnectclient_user_mapping
                WHERE user_openidconnect_identifier = $identifier AND provider_id = $provider_id";
        return $this->retrieveFirstRow($sql);
    }

    public function searchByProviderIdAndUserId($provider_id, $user_id)
    {
        $provider_id = $this->getDa()->escapeInt($provider_id);
        $user_id     = $this->getDa()->escapeInt($user_id);

        $sql = "SELECT * FROM plugin_openidconnectclient_user_mapping
                WHERE user_id = $user_id AND provider_id = $provider_id";
        return $this->retrieveFirstRow($sql);
    }

    public function searchUsageByUserId($user_id)
    {
        $user_id = $this->getDa()->escapeInt($user_id);

        $sql = "SELECT mapping.id AS user_mapping_id, mapping.provider_id, provider.name, provider.icon,
                  provider.unique_authentication_endpoint, mapping.user_id, mapping.last_used
                FROM plugin_openidconnectclient_user_mapping AS mapping
                JOIN plugin_openidconnectclient_provider AS provider ON provider.id = mapping.provider_id
                WHERE mapping.user_id = $user_id
                ORDER BY unique_authentication_endpoint DESC, name ASC";
        return $this->retrieve($sql);
    }
}
