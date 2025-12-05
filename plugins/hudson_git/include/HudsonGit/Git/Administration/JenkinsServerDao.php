<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\HudsonGit\Git\Administration;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Symmetric\EncryptionAdditionalData;
use Tuleap\DB\DataAccessObject;
use Tuleap\DB\UUID;

class JenkinsServerDao extends DataAccessObject
{
    public function addJenkinsServer(int $project_id, string $jenkins_server_url, ?ConcealedString $token): void
    {
        $uuid = $this->uuid_factory->buildUUIDBytes();

        $encrypted_token = null;
        if ($token !== null) {
            $encrypted_token = $this->encryptDataToStoreInATableRow(
                $token,
                $this->getTokenEncryptionAdditionalData($uuid),
            );
        }
        $this->getDB()->insert(
            'plugin_hudson_git_project_server',
            [
                'id' => $uuid,
                'project_id' => $project_id,
                'jenkins_server_url' => $jenkins_server_url,
                'encrypted_token' => $encrypted_token,
            ],
        );
    }

    /**
     * @return array{id: UUID, jenkins_server_url: string, token: ConcealedString|null}[]
     */
    public function getJenkinsServerOfProject(int $project_id): array
    {
        $sql = 'SELECT id, jenkins_server_url, encrypted_token
                FROM plugin_hudson_git_project_server
                WHERE project_id = ?';

        $result = $this->getDB()->run($sql, $project_id);
        $rows   = [];

        foreach ($result as $row) {
            $uuid         = $this->uuid_factory->buildUUIDFromBytesData($row['id']);
            $row['id']    = $uuid;
            $row['token'] = null;
            if ($row['encrypted_token'] !== null) {
                $row['token'] = $this->decryptDataStoredInATableRow(
                    $row['encrypted_token'],
                    $this->getTokenEncryptionAdditionalData($uuid->getBytes())
                );
            }
            unset($row['encrypted_token']);

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @return array{api_id: int, jenkins_server_url: string}[]
     */
    public function getPaginatedJenkinsServerOfProject(
        int $project_id,
        int $limit,
        int $offset,
    ): array {
        $sql = 'SELECT SQL_CALC_FOUND_ROWS api_id, jenkins_server_url
                FROM plugin_hudson_git_project_server
                WHERE project_id = ?
                LIMIT ? OFFSET ?
                ';

        return $this->getDB()->run($sql, $project_id, $limit, $offset);
    }

    public function isJenkinsServerAlreadyDefinedInProject(int $project_id, string $jenkins_server_url): bool
    {
        $sql = 'SELECT NULL
                FROM plugin_hudson_git_project_server
                WHERE project_id = ?
                AND jenkins_server_url = ?';

        $rows = $this->getDB()->row($sql, $project_id, $jenkins_server_url);

        if (empty($rows)) {
            return false;
        }

        return count($rows) > 0;
    }

    /**
     * @return array{project_id: int}|null
     */
    public function getProjectIDByJenkinsServerID(string $jenkins_server_uuid_hex): ?array
    {
        $sql = 'SELECT project_id
                FROM plugin_hudson_git_project_server
                WHERE id = ?';

        return $this->uuid_factory->buildUUIDFromHexadecimalString($jenkins_server_uuid_hex)->mapOr(
            fn(UUID $uuid): array => $this->getDB()->row($sql, $uuid->getBytes()),
            null
        );
    }

    public function deleteJenkinsServer(string $jenkins_server_uuid_hex): void
    {
        $this->uuid_factory->buildUUIDFromHexadecimalString($jenkins_server_uuid_hex)->apply(
            fn(UUID $uuid) => $this->getDB()->delete(
                'plugin_hudson_git_project_server',
                [
                    'id' => $uuid->getBytes(),
                ]
            ),
        );
    }

    /**
     * @param non-empty-string $uuid
     */
    private function getTokenEncryptionAdditionalData(string $uuid): EncryptionAdditionalData
    {
        return new EncryptionAdditionalData(
            'plugin_hudson_git_project_server',
            'encrypted_token',
            $uuid
        );
    }
}
