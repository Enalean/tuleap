<?php
/**
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Artidoc\Upload\Section\File;

use DateTimeImmutable;
use Tuleap\DB\DataAccessObject;
use Tuleap\Tus\Identifier\FileIdentifier;
use Tuleap\Tus\Identifier\FileIdentifierFactory;

class OngoingUploadDao extends DataAccessObject implements DeleteUnusedFiles, SaveFileUpload, SearchFileUpload, DeleteFileUpload, SearchFileOngoingUploadIds, DeleteUnusableFiles
{
    public function __construct(private FileIdentifierFactory $identifier_factory)
    {
        parent::__construct();
    }

    public function saveFileOnGoingUpload(InsertFileToUpload $file_to_upload): FileIdentifier
    {
        $id = $this->identifier_factory->buildIdentifier();

        $this->getDB()->insert(
            'plugin_artidoc_section_upload',
            [
                'id'              => $id->getBytes(),
                'file_name'       => $file_to_upload->name,
                'file_size'       => $file_to_upload->size,
                'user_id'         => $file_to_upload->user_id,
                'expiration_date' => $file_to_upload->expiration_date,
                'item_id'         => $file_to_upload->artidoc_id,
            ]
        );

        return $id;
    }

    /**
     * @return list<array{id: FileIdentifier, file_size: int, file_name: string, item_id: int, user_id: int}>
     */
    public function searchFileOngoingUploadByItemIdNameAndExpirationDate(int $item_id, string $name, DateTimeImmutable $current_time): array
    {
        /**
         * @var list<array{id: int, file_size: int, file_name: string, item_id: int, user_id: int}> $rows
         */
        $rows = $this->getDB()->run(
            <<<EOS
            SELECT id, file_size, file_name, item_id, user_id
            FROM plugin_artidoc_section_upload
            WHERE item_id = ? AND file_name = ? AND expiration_date > ?
            EOS,
            $item_id,
            $name,
            $current_time->getTimestamp(),
        );

        return array_map(
            function (array $row) {
                $row['id'] = $this->identifier_factory->buildFromBytesData((string) $row['id']);

                return $row;
            },
            $rows,
        );
    }

    public function deleteById(FileIdentifier $id): void
    {
        $this->getDB()->delete('plugin_artidoc_section_upload', [
            'id' => $id->getBytes(),
        ]);
    }

    public function deleteUnusableFile(DateTimeImmutable $current_time): void
    {
        $this->getDB()->run('DELETE FROM plugin_artidoc_section_upload WHERE expiration_date <= ?', $current_time->getTimestamp());
    }

    /**
     * @return array{id: FileIdentifier, file_size: int, file_name: string, item_id: int, user_id: int, expiration_date: int} | null
     */
    public function searchFileOngoingUploadById(FileIdentifier $id): ?array
    {
        $row = $this->getDB()->row('SELECT * FROM plugin_artidoc_section_upload WHERE id = ?', $id->getBytes());

        if (! empty($row)) {
            $row['id'] = $this->identifier_factory->buildFromBytesData($row['id']);
        }

        return $row;
    }

    public function searchFileOngoingUploadIds(): array
    {
        /**
         * @var list<string> $ids
         */
        $ids = $this->getDB()->column('SELECT id FROM plugin_artidoc_section_upload');

        return array_map(
            fn (string $id) => $this->identifier_factory->buildFromBytesData($id),
            $ids,
        );
    }

    public function deleteUnusableFiles(\DateTimeImmutable $current_time): void
    {
        $this->getDB()->run(
            'DELETE FROM plugin_artidoc_section_upload WHERE expiration_date <= ?',
            $current_time->getTimestamp()
        );
    }
}
