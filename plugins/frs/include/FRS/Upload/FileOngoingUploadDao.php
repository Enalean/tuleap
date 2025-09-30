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

namespace Tuleap\FRS\Upload;

use Tuleap\DB\DataAccessObject;

class FileOngoingUploadDao extends DataAccessObject
{
    public function searchFileOngoingUploadByReleaseIDNameAndExpirationDate(
        int $release_id,
        string $name,
        int $current_time,
    ): array {
        $sql = 'SELECT *
                FROM plugin_frs_file_upload
                WHERE release_id = ? AND name = ? AND expiration_date > ?';

        return $this->getDB()->run($sql, $release_id, $name, $current_time);
    }

    public function saveFileOngoingUpload(
        int $expiration_date,
        int $release_id,
        string $name,
        int $file_size,
        int $user_id,
    ): int {
        return (int) $this->getDB()->insertReturnId(
            'plugin_frs_file_upload',
            [
                'expiration_date' => $expiration_date,
                'release_id'      => $release_id,
                'name'            => $name,
                'user_id'         => $user_id,
                'file_size'       => $file_size,
            ]
        );
    }

    public function searchFileOngoingUploadByIDUserIDAndExpirationDate(
        int $id,
        int $user_id,
        int $current_time,
    ): array {
        return $this->getDB()->row(
            'SELECT * FROM plugin_frs_file_upload WHERE id = ? AND user_id = ? AND expiration_date > ?',
            $id,
            $user_id,
            $current_time
        );
    }

    public function deleteByItemID(int $id): void
    {
        $this->getDB()->delete('plugin_frs_file_upload', ['id' => $id]);
    }

    public function searchFileOngoingUploadById(int $id): array
    {
        return $this->getDB()->row('SELECT * FROM plugin_frs_file_upload WHERE id = ?', $id);
    }

    public function deleteUnusableFiles($current_time): void
    {
        $this->getDB()->run('DELETE FROM plugin_frs_file_upload WHERE ? >= expiration_date', $current_time);
    }

    public function searchFileOngoingUploadIds(): array
    {
        return $this->getDB()->column('SELECT id FROM plugin_frs_file_upload');
    }
}
