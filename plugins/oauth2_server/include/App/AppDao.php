<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\OAuth2Server\App;

use Tuleap\DB\DataAccessObject;

class AppDao extends DataAccessObject
{
    /**
     * @psalm-return array{id:int, project_id:int, name:string, redirect_endpoint: string}
     */
    public function searchByClientId(ClientIdentifier $client_id): ?array
    {
        $sql = 'SELECT * FROM plugin_oauth2_server_app
            WHERE id = ?';
        return $this->getDB()->row($sql, $client_id->getInternalId());
    }

    /**
     * @psalm-return array<array{id:int, project_id:int, name:string, redirect_endpoint: string}>
     */
    public function searchByProject(\Project $project): array
    {
        $sql = "SELECT * FROM plugin_oauth2_server_app
            WHERE project_id = ?";
        return $this->getDB()->run($sql, $project->getID());
    }

    public function create(NewOAuth2App $app): void
    {
        $this->getDB()->insert(
            'plugin_oauth2_server_app',
            [
                'project_id'        => $app->getProject()->getID(),
                'name'              => $app->getName(),
                'redirect_endpoint' => $app->getRedirectEndpoint()
            ]
        );
    }

    public function delete(int $app_id): void
    {
        $this->getDB()->run('DELETE FROM plugin_oauth2_server_app WHERE id = ?', $app_id);
    }
}
