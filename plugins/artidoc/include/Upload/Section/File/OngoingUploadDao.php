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
use Override;
use Tuleap\DB\DataAccessObject;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tus\Identifier\FileIdentifier;
use Tuleap\Tus\Identifier\FileIdentifierFactory;

class OngoingUploadDao extends DataAccessObject implements SaveFileUpload, DeleteFileUpload, SearchExpiredUploads, DeleteExpiredFiles, SearchUpload, SearchNotExpiredOngoingUpload, RemoveExpirationDate
{
    public function __construct(private FileIdentifierFactory $identifier_factory)
    {
        parent::__construct();
    }

    #[Override]
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

    #[Override]
    public function deleteById(FileIdentifier $id): void
    {
        $this->getDB()->delete('plugin_artidoc_section_upload', [
            'id' => $id->getBytes(),
        ]);
    }

    #[Override]
    public function searchExpiredUploads(\DateTimeImmutable $current_time): array
    {
        /**
         * @var list<array{id: string, item_id: int, file_size: int, file_name: string}> $rows
         */
        $rows = $this->getDB()->run(
            'SELECT id, file_name, file_size, item_id FROM plugin_artidoc_section_upload WHERE expiration_date <= ?',
            $current_time->getTimestamp(),
        );

        return array_map(
            fn (array $row) => new ExpiredFileInformation(
                $row['item_id'],
                $this->identifier_factory->buildFromBytesData($row['id']),
                $row['file_name'],
                $row['file_size'],
            ),
            $rows,
        );
    }

    #[Override]
    public function searchUpload(FileIdentifier $id): Ok|Err
    {
        $row = $this->getDB()->row(
            'SELECT * FROM plugin_artidoc_section_upload WHERE id = ?',
            $id->getBytes(),
        );

        return $this->toUploadFileInformation($row);
    }

    #[Override]
    public function searchNotExpiredOngoingUpload(FileIdentifier $id, int $user_id, \DateTimeImmutable $current_time): Ok|Err
    {
        $row = $this->getDB()->row(
            'SELECT * FROM plugin_artidoc_section_upload WHERE id = ? AND user_id = ? AND expiration_date > ?',
            $id->getBytes(),
            $user_id,
            $current_time->getTimestamp(),
        );

        return $this->toUploadFileInformation($row);
    }

    /**
     * @param null | array{id: string, item_id: int, file_size: int, file_name: string} $row
     * @return Ok<UploadFileInformation>|Err<Fault>
     */
    private function toUploadFileInformation(?array $row): Ok|Err
    {
        if ($row === null) {
            return Result::err(Fault::fromMessage('Unable to find uploaded file'));
        }

        return Result::ok(
            new UploadFileInformation(
                $row['item_id'],
                $this->identifier_factory->buildFromBytesData($row['id']),
                $row['file_name'],
                $row['file_size'],
            ),
        );
    }

    #[Override]
    public function deleteExpiredFiles(\DateTimeImmutable $current_time): void
    {
        $this->getDB()->run(
            'DELETE FROM plugin_artidoc_section_upload WHERE expiration_date <= ?',
            $current_time->getTimestamp()
        );
    }

    #[Override]
    public function removeExpirationDate(FileIdentifier $id): void
    {
        $this->getDB()->run(
            'UPDATE plugin_artidoc_section_upload SET expiration_date = NULL WHERE id = ?',
            $id->getBytes(),
        );
    }
}
