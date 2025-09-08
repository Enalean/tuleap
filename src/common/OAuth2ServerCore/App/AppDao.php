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

namespace Tuleap\OAuth2ServerCore\App;

use Tuleap\DB\DataAccessObject;

class AppDao extends DataAccessObject implements RetrieveAppMatchingClientID
{
    /**
     * @psalm-return array{id:int, project_id:int|null, name:string, redirect_endpoint: string, use_pkce:0|1, app_type: string}
     */
    #[\Override]
    public function searchByClientId(ClientIdentifier $client_id): ?array
    {
        $sql = 'SELECT id, project_id, name, redirect_endpoint, use_pkce, app_type FROM oauth2_server_app
            WHERE id = ?';
        return $this->getDB()->row($sql, $client_id->getInternalId());
    }

    public function searchClientSecretByClientID(int $client_id): ?string
    {
        $row = $this->getDB()->row(
            'SELECT verifier FROM oauth2_server_app WHERE id = ?',
            $client_id
        );

        return $row['verifier'] ?? null;
    }

    public function searchProjectIDByClientID(int $client_id): ?int
    {
        $row = $this->getDB()->row(
            'SELECT project_id FROM oauth2_server_app WHERE id = ?',
            $client_id
        );

        return $row['project_id'] ?? null;
    }

    /**
     * @psalm-return array<array{id:int, project_id:int, name:string, redirect_endpoint: string, use_pkce:0|1}>
     */
    public function searchByProject(\Project $project, string $app_type): array
    {
        $sql = 'SELECT id, project_id, name, redirect_endpoint, use_pkce
                FROM oauth2_server_app
                WHERE project_id = ?
                    AND app_type = ?';

        return $this->getDB()->run($sql, $project->getID(), $app_type);
    }

    /**
     * @psalm-return array<array{id:int, name:string, redirect_endpoint: string, use_pkce:0|1}>
     */
    public function searchSiteLevelApps(string $app_type): array
    {
        return $this->getDB()->run(
            'SELECT id, name, redirect_endpoint, use_pkce FROM oauth2_server_app
             WHERE project_id IS NULL
                AND app_type = ?',
            $app_type
        );
    }

    /**
     * @psalm-return array<array{id:int, project_id:int|null, name:string, redirect_endpoint:string, use_pkce:0|1}>
     */
    public function searchAuthorizedAppsByUser(\PFUser $user, string $app_type): array
    {
        $sql = 'SELECT app.id, project_id, name, redirect_endpoint, use_pkce
                FROM oauth2_server_app AS app
                JOIN plugin_oauth2_authorization AS authorization ON app.id = authorization.app_id
                WHERE authorization.user_id = ?
                    AND app_type = ?';

        return $this->getDB()->run($sql, $user->getId(), $app_type);
    }

    public function create(NewOAuth2App $app): int
    {
        $project = $app->getProject();
        $this->getDB()->insert(
            'oauth2_server_app',
            [
                'project_id'        => $project !== null ? $project->getID() : null,
                'name'              => $app->getName(),
                'redirect_endpoint' => $app->getRedirectEndpoint(),
                'verifier'          => $app->getHashedSecret(),
                'use_pkce'          => $app->isUsingPKCE(),
                'app_type'          => $app->getAppType(),
            ]
        );
        return (int) $this->getDB()->lastInsertId();
    }

    public function updateSecret(int $app_id, string $hashed_secret): void
    {
        $this->getDB()->update(
            'oauth2_server_app',
            ['verifier' => $hashed_secret],
            ['id' => $app_id]
        );
    }

    public function updateApp(OAuth2App $updated_app): void
    {
        $this->getDB()->update(
            'oauth2_server_app',
            [
                'name' => $updated_app->getName(),
                'redirect_endpoint' => $updated_app->getRedirectEndpoint(),
                'use_pkce' => $updated_app->isUsingPKCE(),
            ],
            ['id' => $updated_app->getId()]
        );
    }

    public function delete(int $app_id): void
    {
        $this->getDB()->run('DELETE FROM oauth2_server_app WHERE id = ?', $app_id);
    }

    public function deleteAppsInNonExistingOrDeletedProject(): void
    {
        $this->getDB()->run(
            'DELETE oauth2_server_app.*
            FROM oauth2_server_app
            LEFT JOIN `groups` ON oauth2_server_app.project_id = `groups`.group_id
            WHERE `groups`.status = "D" OR (`groups`.group_id IS NULL AND oauth2_server_app.project_id IS NOT NULL)'
        );
    }
}
