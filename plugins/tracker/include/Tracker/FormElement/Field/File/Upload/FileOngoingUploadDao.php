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

namespace Tuleap\Tracker\FormElement\Field\File\Upload;

use ParagonIE\EasyDB\EasyDB;
use Tuleap\DB\DataAccessObject;

class FileOngoingUploadDao extends DataAccessObject
{
    public function searchFileOngoingUploadByFieldIdNameAndExpirationDate(
        int $field_id,
        string $name,
        int $current_time
    ): array {
        $sql = 'SELECT *
                FROM plugin_tracker_file_upload AS upload
                    INNER JOIN tracker_fileinfo AS fileinfo
                        ON fileinfo.id = upload.fileinfo_id
                WHERE upload.field_id = ?
                  AND fileinfo.filename = ?
                  AND upload.expiration_date > ?';

        return $this->getDB()->run($sql, $field_id, $name, $current_time);
    }

    public function saveFileOngoingUpload(
        int $expiration_date,
        int $field_id,
        string $filename,
        int $filesize,
        string $filetype,
        int $user_id
    ): int {
        $this->getDB()->tryFlatTransaction(function (EasyDB $db) use (
            $expiration_date,
            $field_id,
            $filename,
            $filesize,
            $filetype,
            $user_id,
            &$id
        ): void {
            $id = (int) $db->insertReturnId(
                'tracker_fileinfo',
                [
                    'submitted_by' => $user_id,
                    'description'  => '',
                    'filename'     => $filename,
                    'filesize'     => $filesize,
                    'filetype'     => $filetype
                ]
            );
            $db->insert(
                'plugin_tracker_file_upload',
                [
                    'fileinfo_id' => $id,
                    'expiration_date' => $expiration_date,
                    'field_id'        => $field_id,
                ]
            );
        });

        return $id;
    }

    public function deleteByItemID(int $id): void
    {
        $this->getDB()->run(
            'DELETE upload, fileinfo
                FROM plugin_tracker_file_upload AS upload
                    INNER JOIN tracker_fileinfo AS fileinfo
                        ON fileinfo.id = upload.fileinfo_id
                WHERE fileinfo.id = ?',
            $id
        );
    }

    public function searchFileOngoingUploadByIDUserIDAndExpirationDate(
        int $id,
        int $user_id,
        int $current_time
    ): ?array {
        return $this->getDB()->row(
            'SELECT *
                FROM plugin_tracker_file_upload AS upload
                    INNER JOIN tracker_fileinfo AS fileinfo
                        ON fileinfo.id = upload.fileinfo_id
                WHERE fileinfo.id = ?
                  AND fileinfo.submitted_by = ?
                  AND upload.expiration_date > ?',
            $id,
            $user_id,
            $current_time
        );
    }

    public function searchFileOngoingUploadById(int $id): ?array
    {
        return $this->getDB()->row(
            'SELECT *
                FROM plugin_tracker_file_upload AS upload
                    INNER JOIN tracker_fileinfo AS fileinfo
                        ON fileinfo.id = upload.fileinfo_id
                WHERE fileinfo.id = ?',
            $id
        );
    }

    public function deleteUnusableFiles(int $current_time): void
    {
        $this->getDB()->run(
            'DELETE upload, fileinfo
                FROM plugin_tracker_file_upload AS upload
                    INNER JOIN tracker_fileinfo AS fileinfo
                        ON fileinfo.id = upload.fileinfo_id
                WHERE ? >= upload.expiration_date',
            $current_time
        );
    }

    public function searchUnusableFiles(int $current_time): array
    {
        return $this->getDB()->run(
            'SELECT *
                FROM plugin_tracker_file_upload AS upload
                    INNER JOIN tracker_fileinfo AS fileinfo
                        ON fileinfo.id = upload.fileinfo_id
                WHERE ? >= upload.expiration_date',
            $current_time
        );
    }

    public function deleteUploadedFileThatIsAttached(int $fileinfo_id): void
    {
        $this->getDB()->run(
            'DELETE FROM plugin_tracker_file_upload WHERE fileinfo_id = ?',
            $fileinfo_id
        );
    }
}
