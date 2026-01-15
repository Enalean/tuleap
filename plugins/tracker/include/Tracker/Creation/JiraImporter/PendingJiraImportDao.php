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

namespace Tuleap\Tracker\Creation\JiraImporter;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Symmetric\EncryptionAdditionalData;
use Tuleap\DB\DataAccessObject;
use Tuleap\DB\UUID;

class PendingJiraImportDao extends DataAccessObject
{
    public function create(
        int $project_id,
        int $user_id,
        string $jira_server,
        string $jira_user_email,
        ConcealedString $jira_token,
        string $jira_project_id,
        string $jira_issue_type_name,
        string $jira_issue_type_id,
        string $tracker_name,
        string $tracker_shortname,
        string $tracker_color,
        string $tracker_description,
    ): UUID {
        $uuid_bytes = $this->uuid_factory->buildUUIDBytes();
        $id         = $this->uuid_factory->buildUUIDFromBytesData($uuid_bytes);
        $this->getDB()->insert(
            'plugin_tracker_pending_jira_import',
            [
                'id' => $uuid_bytes,
                'created_on'           => (new \DateTimeImmutable())->getTimestamp(),
                'project_id'           => $project_id,
                'user_id'              => $user_id,
                'jira_server'          => $jira_server,
                'jira_user_email'      => $jira_user_email,
                'encrypted_jira_token' => $this->encryptDataToStoreInATableRow(
                    $jira_token,
                    $this->getJIRATokenEncryptionAdditionalData($id)
                ),
                'jira_project_id'      => $jira_project_id,
                'jira_issue_type_name' => $jira_issue_type_name,
                'jira_issue_type_id'   => $jira_issue_type_id,
                'tracker_name'         => $tracker_name,
                'tracker_shortname'    => $tracker_shortname,
                'tracker_color'        => $tracker_color,
                'tracker_description'  => $tracker_description,
            ]
        );
        return $id;
    }

    /**
     * @return list<array{tracker_name:string,tracker_shortname:string}>
     */
    public function searchByProjectId(int $project_id): array
    {
        return $this->getDB()->run(
            'SELECT tracker_name, tracker_shortname FROM plugin_tracker_pending_jira_import WHERE project_id = ?',
            $project_id
        );
    }

    /**
     * @return ?array{id: UUID, project_id: int, user_id: int, created_on: int, jira_server: string, jira_user_email: string, jira_token:ConcealedString, jira_project_id: string, jira_issue_type_name: string, jira_issue_type_id: string, tracker_name: string, tracker_shortname: string, tracker_color: string, tracker_description: string}
     */
    public function searchById(UUID $id): ?array
    {
        $row = $this->getDB()->row(
            '
            SELECT project_id, user_id, created_on, jira_server, jira_user_email, encrypted_jira_token, jira_project_id,
                   jira_issue_type_name, jira_issue_type_id, tracker_name, tracker_shortname, tracker_color, tracker_description
            FROM plugin_tracker_pending_jira_import WHERE id = ?',
            $id->getBytes()
        );
        if ($row === null) {
            return null;
        }
        $row['id'] = $id;

        $row['jira_token'] = $this->decryptDataStoredInATableRow(
            $row['encrypted_jira_token'],
            $this->getJIRATokenEncryptionAdditionalData($id)
        );
        sodium_memzero($row['encrypted_jira_token']);

        return $row;
    }

    public function getJIRATokenEncryptionAdditionalData(UUID $id): EncryptionAdditionalData
    {
        return new EncryptionAdditionalData('plugin_tracker_pending_jira_import', 'encrypted_jira_token', $id->getBytes());
    }

    public function doesTrackerShortNameExist(string $shortname, int $project_id): bool
    {
        $exists = $this->getDB()->single(
            'SELECT NULL
                FROM plugin_tracker_pending_jira_import
                WHERE project_id = ? AND tracker_shortname = ?',
            [$project_id, $shortname]
        );

        return $exists !== false;
    }

    public function doesTrackerNameExist(string $name, int $project_id): bool
    {
        $exists = $this->getDB()->single(
            'SELECT NULL
                FROM plugin_tracker_pending_jira_import
                WHERE project_id = ? AND tracker_name = ?',
            [$project_id, $name]
        );

        return $exists !== false;
    }

    public function deleteExpiredImports(int $expiration_timestamp): void
    {
        $this->getDB()->run(
            'DELETE
                FROM plugin_tracker_pending_jira_import
                WHERE created_on <= ?',
            $expiration_timestamp
        );
    }

    /**
     * @return list<array{id: UUID, project_id: int, user_id: int, created_on: int, jira_server: string, jira_user_email: string, jira_token:ConcealedString, jira_project_id: string, jira_issue_type_name: string, jira_issue_type_id: string, tracker_name: string, tracker_shortname: string, tracker_color: string, tracker_description: string}>
     */
    public function searchExpiredImports(int $expiration_timestamp): array
    {
        $retrieved_rows = $this->getDB()->run(
            'SELECT id, project_id, user_id, created_on, jira_server, jira_user_email, encrypted_jira_token, jira_project_id,
                   jira_issue_type_name, jira_issue_type_id, tracker_name, tracker_shortname, tracker_color, tracker_description
                FROM plugin_tracker_pending_jira_import
                WHERE created_on <= ?',
            $expiration_timestamp
        );

        $rows = [];

        foreach ($retrieved_rows as $row) {
            $id        = $this->uuid_factory->buildUUIDFromBytesData($row['id']);
            $row['id'] = $id;

            $row['jira_token'] = $this->decryptDataStoredInATableRow(
                $row['encrypted_jira_token'],
                $this->getJIRATokenEncryptionAdditionalData($id)
            );
            sodium_memzero($row['encrypted_jira_token']);
        }

        return $rows;
    }

    public function deleteById(UUID $id): void
    {
        $this->getDB()->delete('plugin_tracker_pending_jira_import', [
            'id' => $id->getBytes(),
        ]);
    }
}
