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

    public function searchConfiguredProviders() {
        $sql = "SELECT * FROM plugin_openidconnectclient_provider WHERE client_id != '' AND client_secret != ''";
        return $this->retrieve($sql);
    }
}