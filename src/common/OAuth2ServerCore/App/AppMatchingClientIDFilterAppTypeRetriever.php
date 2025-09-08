<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\OAuth2ServerCore\App;

final class AppMatchingClientIDFilterAppTypeRetriever implements RetrieveAppMatchingClientID
{
    public function __construct(private AppDao $app_dao, private string $wanted_app_type)
    {
    }

    /**
     * @psalm-return array{id:int, project_id:int|null, name:string, redirect_endpoint: string, use_pkce:0|1}
     */
    #[\Override]
    public function searchByClientId(ClientIdentifier $client_id): ?array
    {
        $app_data = $this->app_dao->searchByClientId($client_id);
        if ($app_data === null) {
            return null;
        }

        if ($app_data['app_type'] === $this->wanted_app_type) {
            return $app_data;
        }

        return null;
    }
}
