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

class UserMappingDao extends DataAccessObject {

    public function save($user_id, $provider_id, $identifier) {
        $user_id     = $this->getDa()->escapeInt($user_id);
        $provider_id = $this->getDa()->escapeInt($provider_id);
        $identifier  = $this->getDa()->quoteSmart($identifier);

        $sql = "INSERT INTO plugin_openidconnectclient_user_mapping(user_id, provider_id, user_openidconnect_identifier)
                VALUES($user_id, $provider_id, $identifier)";
        return $this->update($sql);
    }

    public function getUserId($identifier) {
        $identifier = $this->getDa()->quoteSmart($identifier);
        $sql        = 'SELECT user_id FROM plugin_openidconnectclient_user_mapping WHERE user_openidconnect_identifier = ' . $identifier;
        return $this->retrieve($sql);

    }

    public function searchByIdentifierAndProviderId($identifier, $provider_id) {
        $identifier  = $this->getDa()->quoteSmart($identifier);
        $provider_id = $this->getDa()->escapeInt($provider_id);

        $sql = "SELECT * FROM plugin_openidconnectclient_user_mapping
                WHERE user_openidconnect_identifier = $identifier AND provider_id = $provider_id";
        return $this->retrieveFirstRow($sql);
    }

}