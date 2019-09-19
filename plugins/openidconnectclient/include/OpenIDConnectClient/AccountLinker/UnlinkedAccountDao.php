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

namespace Tuleap\OpenIDConnectClient\AccountLinker;

use DataAccessObject;

class UnlinkedAccountDao extends DataAccessObject
{

    public function searchById($id)
    {
        $id  = $this->getDa()->quoteSmart($id);
        $sql = "SELECT * FROM plugin_openidconnectclient_unlinked_account WHERE id = $id";
        return $this->retrieveFirstRow($sql);
    }

    public function save($id, $provider_id, $user_identifier)
    {
        $id          = $this->getDa()->quoteSmart($id);
        $provider_id = $this->getDa()->escapeInt($provider_id);
        $identifier  = $this->getDa()->quoteSmart($user_identifier);

        $sql = "INSERT INTO plugin_openidconnectclient_unlinked_account(id, provider_id, openidconnect_identifier)
                VALUES ($id, $provider_id, $identifier)";
        return $this->update($sql);
    }

    public function deleteById($id)
    {
        $id = $this->getDa()->quoteSmart($id);
        $sql = "DELETE FROM plugin_openidconnectclient_unlinked_account WHERE id = $id";
        return $this->update($sql);
    }
}
