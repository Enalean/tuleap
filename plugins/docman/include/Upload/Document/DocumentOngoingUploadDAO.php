<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Docman\Upload\Document;

use Docman_PermissionsManager;
use Tuleap\DB\DataAccessObject;

class DocumentOngoingUploadDAO extends DataAccessObject
{
    /**
     * @psalm-return array{user_id: int, filename: string, filesize: int, item_id: int}[]
     */
    public function searchDocumentOngoingUploadByParentIDTitleAndExpirationDate(
        $parent_id,
        $title,
        $current_time,
    ): array {
        $sql = 'SELECT user_id, filename, filesize, item_id
                FROM plugin_docman_new_document_upload
                WHERE parent_id = ? AND title = ? AND expiration_date > ?';

        return $this->getDB()->run($sql, $parent_id, $title, $current_time);
    }

    public function searchDocumentOngoingUploadByItemID($item_id)
    {
        return $this->getDB()->row(
            'SELECT plugin_docman_new_document_upload.*, `groups`.group_id
             FROM plugin_docman_new_document_upload
             JOIN plugin_docman_item ON (plugin_docman_item.item_id = plugin_docman_new_document_upload.parent_id)
             JOIN `groups` ON (`groups`.group_id = plugin_docman_item.group_id)
             WHERE plugin_docman_new_document_upload.item_id = ?',
            $item_id
        );
    }

    public function searchDocumentOngoingUploadByItemIDUserIDAndExpirationDate($item_id, $user_id, $current_time)
    {
        return $this->getDB()->row(
            'SELECT * FROM plugin_docman_new_document_upload WHERE item_id = ? AND user_id = ? AND expiration_date > ?',
            $item_id,
            $user_id,
            $current_time
        );
    }

    /**
     * @return array
     */
    public function searchDocumentOngoingUploadItemIDs()
    {
        return $this->getDB()->column('SELECT item_id FROM plugin_docman_new_document_upload');
    }

    public function saveDocumentOngoingUpload(
        int $expiration_date,
        int $parent_id,
        string $title,
        string $description,
        int $user_id,
        string $filename,
        int $filesize,
        ?int $status,
        ?int $obsolescence_date,
    ): int {
        $item_id = (int) $this->getDB()->insertReturnId('plugin_docman_item_id', []);
        $this->getDB()->insert(
            'plugin_docman_new_document_upload',
            [
                'item_id'           => $item_id,
                'expiration_date'   => $expiration_date,
                'parent_id'         => $parent_id,
                'title'             => $title,
                'description'       => $description,
                'user_id'           => $user_id,
                'filename'          => $filename,
                'filesize'          => $filesize,
                'status'            => $status,
                'obsolescence_date' => $obsolescence_date,
            ]
        );
        return $item_id;
    }

    public function updateDocumentFilenameOngoingUpload(
        int $item_id,
        string $new_filename,
    ): void {
        $this->getDB()->update(
            'plugin_docman_new_document_upload',
            [
                'filename' => $new_filename,
            ],
            [
                'item_id' => $item_id,
            ]
        );
    }

    public function deleteUnusableDocuments($current_time): void
    {
        $this->getDB()->run(
            'DELETE plugin_docman_new_document_upload, plugin_docman_item_id, plugin_docman_metadata_value, permissions
             FROM plugin_docman_new_document_upload
             JOIN plugin_docman_item_id ON (plugin_docman_item_id.id = plugin_docman_new_document_upload.item_id)
             LEFT JOIN plugin_docman_metadata_value ON (plugin_docman_new_document_upload.item_id = plugin_docman_metadata_value.item_id)
             LEFT JOIN permissions ON (
                    CAST(plugin_docman_new_document_upload.item_id AS CHAR CHARACTER SET utf8) = permissions.object_id AND
                    permissions.permission_type IN (?, ?, ?)
                 )
             LEFT JOIN plugin_docman_item ON (plugin_docman_item.item_id = plugin_docman_new_document_upload.parent_id)
             LEFT JOIN `groups` ON (`groups`.group_id = plugin_docman_item.group_id)
             WHERE ? >= plugin_docman_new_document_upload.expiration_date OR `groups`.status = "D" OR `groups`.group_id IS NULL',
            Docman_PermissionsManager::ITEM_PERMISSION_TYPE_READ,
            Docman_PermissionsManager::ITEM_PERMISSION_TYPE_WRITE,
            Docman_PermissionsManager::ITEM_PERMISSION_TYPE_MANAGE,
            $current_time
        );
    }

    public function deleteByItemID($item_id)
    {
        $this->getDB()->delete('plugin_docman_new_document_upload', ['item_id' => $item_id]);
    }
}
