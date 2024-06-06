<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Document;

use ParagonIE\EasyDB\EasyDB;
use Tuleap\DB\DataAccessObject;
use Tuleap\DB\InvalidUuidStringException;
use Tuleap\DB\UUID;

final class ArtidocDao extends DataAccessObject implements SearchArtidocDocument, SearchOneSection, SearchPaginatedRawSections, SaveSections, SearchConfiguredTracker, SaveConfiguredTracker
{
    public function searchByItemId(int $item_id): ?array
    {
        return $this->getDB()->row(
            <<<EOS
            SELECT *
            FROM plugin_docman_item
            WHERE item_id = ?
              AND item_type = ?
              AND other_type = ?
              AND delete_date IS NULL
            EOS,
            $item_id,
            \Docman_Item::TYPE_OTHER,
            ArtidocDocument::TYPE,
        );
    }

    public function searchSectionById(string $section_id): ?array
    {
        try {
            $row = $this->getDB()->row(
                <<<EOS
            SELECT *
            FROM plugin_artidoc_document
            WHERE id = ?
            EOS,
                $this->uuid_factory->buildUUIDFromHexadecimalString($section_id)->getBytes(),
            );
        } catch (InvalidUuidStringException) {
            return null;
        }

        if ($row) {
            $row['id'] = $this->uuid_factory->buildUUIDFromBytesData($row['id']);
        }

        return $row;
    }

    public function searchPaginatedRawSectionsByItemId(int $item_id, int $limit, int $offset): PaginatedRawSections
    {
        return $this->getDB()->tryFlatTransaction(function (EasyDB $db) use ($item_id, $limit, $offset) {
            $rows = $db->run(
                <<<EOS
                SELECT id, artifact_id
                FROM plugin_artidoc_document
                WHERE item_id = ?
                ORDER BY `rank`
                LIMIT ? OFFSET ?
                EOS,
                $item_id,
                $limit,
                $offset,
            );

            $total = $db->cell('SELECT COUNT(*) FROM plugin_artidoc_document WHERE item_id = ?', $item_id);

            return new PaginatedRawSections(
                $item_id,
                array_values(
                    array_map(
                        /**
                         * @param array{id: string, artifact_id: int} $row
                         * @return array{id: UUID, artifact_id: int} $row
                         */
                        function (array $row): array {
                            $row['id'] = $this->uuid_factory->buildUUIDFromBytesData($row['id']);

                            return $row;
                        },
                        $rows,
                    ),
                ),
                $total,
            );
        });
    }

    public function cloneItem(int $source_id, int $target_id): void
    {
        $this->getDB()->tryFlatTransaction(function (EasyDB $db) use ($source_id, $target_id) {
            $rows = $db->run(
                'SELECT artifact_id, `rank`
                FROM plugin_artidoc_document
                WHERE item_id = ?',
                $source_id
            );

            $db->insertMany(
                'plugin_artidoc_document',
                array_map(
                    function (array $row) use ($target_id) {
                        return [
                            'id'          => $this->uuid_factory->buildUUIDBytes(),
                            'item_id'     => $target_id,
                            'artifact_id' => $row['artifact_id'],
                            'rank'        => $row['rank'],
                        ];
                    },
                    $rows,
                )
            );

            $db->run('DELETE FROM plugin_artidoc_document_tracker WHERE item_id = ?', $target_id);
            $db->run(
                'INSERT INTO plugin_artidoc_document_tracker (item_id, tracker_id)
                SELECT ?, tracker_id
                FROM plugin_artidoc_document_tracker
                WHERE item_id = ?',
                $target_id,
                $source_id
            );
        });
    }

    public function save(int $item_id, array $artifact_ids): void
    {
        $this->getDB()->tryFlatTransaction(function (EasyDB $db) use ($item_id, $artifact_ids) {
            $db->run('DELETE FROM plugin_artidoc_document WHERE item_id = ?', $item_id);

            if (count($artifact_ids) > 0) {
                $rank = 0;
                $db->insertMany(
                    'plugin_artidoc_document',
                    array_map(
                        function ($artifact_id) use ($item_id, &$rank) {
                            return [
                                'id'          => $this->uuid_factory->buildUUIDBytes(),
                                'item_id'     => $item_id,
                                'artifact_id' => $artifact_id,
                                'rank'        => $rank++,
                            ];
                        },
                        $artifact_ids,
                    ),
                );
            }
        });
    }

    public function getTracker(int $item_id): ?int
    {
        return $this->getDB()->cell(
            'SELECT tracker_id
            FROM plugin_artidoc_document_tracker
            WHERE item_id = ?',
            $item_id,
        ) ?: null;
    }

    public function saveTracker(int $item_id, int $tracker_id): void
    {
        $this->getDB()->insertOnDuplicateKeyUpdate(
            'plugin_artidoc_document_tracker',
            [
                'item_id'    => $item_id,
                'tracker_id' => $tracker_id,
            ],
            [
                'tracker_id',
            ]
        );
    }
}
