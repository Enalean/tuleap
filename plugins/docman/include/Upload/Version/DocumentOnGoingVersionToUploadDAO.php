<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\Upload\Version;

use Tuleap\DB\DataAccessObject;

class DocumentOnGoingVersionToUploadDAO extends DataAccessObject
{
    public function saveDocumentVersionOngoingUpload(
        int $expiration_date,
        int $item_id,
        string $version_title,
        string $changelog,
        int $user_id,
        string $filename,
        int $filesize,
        bool $is_file_locked,
        int $status_id,
        int $obsolescence_date_timestamp,
        string $title,
        string $description,
        ?string $approval_table_action,
    ): int {
        $version_id = $this->getDB()->insertReturnId(
            'plugin_docman_new_version_upload',
            [
                'expiration_date'       => $expiration_date,
                'item_id'               => $item_id,
                'version_title'         => $version_title,
                'changelog'             => $changelog,
                'user_id'               => $user_id,
                'filename'              => $filename,
                'filesize'              => $filesize,
                'is_file_locked'        => $is_file_locked,
                'approval_table_action' => $approval_table_action,
                'status'                => $status_id,
                'obsolescence_date'     => $obsolescence_date_timestamp,
                'title'                 => $title,
                'description'           => $description,
            ]
        );
        return (int) $version_id;
    }

    /**
     * @psalm-return array{user_id: int, filename: string, filesize: int, id: int}[]
     */
    public function searchDocumentVersionOngoingUploadByItemIdAndExpirationDate(int $id, int $timestamp): array
    {
        $sql = 'SELECT user_id, filename, filesize, id
                FROM plugin_docman_new_version_upload
                WHERE item_id = ?  AND expiration_date > ?';

        return $this->getDB()->run($sql, $id, $timestamp);
    }

    public function searchDocumentVersionOngoingUploadByVersionIDUserIDAndExpirationDate(int $id, int $user_id, int $timestamp): ?array
    {
        $sql = 'SELECT *
                FROM plugin_docman_new_version_upload
                WHERE id = ? AND user_id = ? AND expiration_date > ?';

        return $this->getDB()->row($sql, $id, $user_id, $timestamp);
    }

    public function searchDocumentVersionOngoingUploadForAnotherUserByItemIdAndExpirationDate(int $id, int $user_id, int $timestamp): array
    {
        $sql = 'SELECT *
                FROM plugin_docman_new_version_upload
                WHERE item_id = ?  AND expiration_date > ? AND user_id != ?';

        return $this->getDB()->run($sql, $id, $timestamp, $user_id);
    }

    public function deleteByVersionID(int $version_id): void
    {
        $sql = 'DELETE
                FROM plugin_docman_new_version_upload
                WHERE id = ?';

        $this->getDB()->run($sql, $version_id);
    }

    public function searchDocumentVersionOngoingUploadByUploadID(int $version_id): array
    {
        $sql = 'SELECT *
                FROM plugin_docman_new_version_upload
                WHERE id = ?';

        return $this->getDB()->row($sql, $version_id);
    }

    public function deleteUnusableVersions(int $timestamp)
    {
        $this->getDB()->run(
            'DELETE plugin_docman_new_version_upload.*
             FROM plugin_docman_new_version_upload
             JOIN plugin_docman_item ON (plugin_docman_item.item_id = plugin_docman_new_version_upload.item_id)
             LEFT JOIN `groups` ON (`groups`.group_id = plugin_docman_item.group_id)
             WHERE ? >= plugin_docman_new_version_upload.expiration_date OR `groups`.status = "D" OR `groups`.group_id IS NULL',
            $timestamp
        );
    }

    public function searchVersionOngoingUploadItemIDs()
    {
        return $this->getDB()->column('SELECT id FROM plugin_docman_new_version_upload');
    }
}
