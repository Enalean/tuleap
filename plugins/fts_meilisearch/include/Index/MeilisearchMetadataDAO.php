<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\FullTextSearchMeilisearch\Index;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;
use Tuleap\FullTextSearchCommon\Index\PlaintextItemToIndex;
use Tuleap\Search\IndexedItemFound;
use Tuleap\Search\IndexedItemsToRemove;

class MeilisearchMetadataDAO extends DataAccessObject
{
    public function saveItemMetadata(PlaintextItemToIndex $item): int
    {
        $existing_entries = $this->searchMatchingEntries($item);

        return $this->getDB()->tryFlatTransaction(
            function () use ($item, $existing_entries): int {
                $nb_existing_entries = count($existing_entries);

                if ($nb_existing_entries > 1) {
                    throw new \LogicException(
                        sprintf(
                            "Do not expect to find more than one indexed item (%s, [%s])",
                            $item->type,
                            print_r($item->metadata, true)
                        )
                    );
                }

                if ($nb_existing_entries === 0) {
                    return $this->createNewEntry($item);
                }

                return reset($existing_entries);
            }
        );
    }

    /**
     * @return int[]
     */
    public function searchMatchingEntries(PlaintextItemToIndex|IndexedItemsToRemove $item): array
    {
        $metadata_statement_filter = $this->getFilterSearchIDFromMetadata($item->metadata);

        return $this->getDB()->column(
            "SELECT id FROM plugin_fts_meilisearch_item WHERE type=? AND $metadata_statement_filter FOR SHARE",
            array_merge([$item->type], $metadata_statement_filter->values())
        );
    }

    /**
     * @return int[]
     */
    public function searchMatchingEntriesByProjectID(int $project_id): array
    {
        return $this->getDB()->column(
            "SELECT id FROM plugin_fts_meilisearch_item WHERE project_id = ? FOR SHARE",
            [$project_id]
        );
    }

    /**
     * @param non-empty-array<non-empty-string,string> $metadata
     */
    private function getFilterSearchIDFromMetadata(array $metadata): EasyStatement
    {
        $metadata_statement_filter = EasyStatement::open();

        foreach ($metadata as $name => $value) {
            $metadata_statement_filter->andWith(
                'plugin_fts_meilisearch_item.id IN (SELECT item_id FROM plugin_fts_meilisearch_metadata WHERE name = ? AND value = ?)',
                $name,
                $value
            );
        }

        return $metadata_statement_filter;
    }

    private function createNewEntry(PlaintextItemToIndex $item): int
    {
        $id                 = $this->getDB()->insertReturnId('plugin_fts_meilisearch_item', ['type' => $item->type, 'project_id' => $item->project_id]);
        $metadata_to_insert = [];
        foreach ($item->metadata as $name => $value) {
            $metadata_to_insert[] = ['item_id' => $id, 'name' => $name, 'value' => $value];
        }
        $this->getDB()->insertMany('plugin_fts_meilisearch_metadata', $metadata_to_insert);

        return (int) $id;
    }

    public function deleteIndexedItemsFromIDs(array $ids_to_remove): void
    {
        if (count($ids_to_remove) === 0) {
            return;
        }

        $this->getDB()->tryFlatTransaction(
            static function (EasyDB $db) use ($ids_to_remove): void {
                $statement_id = EasyStatement::open()->in('id IN (?*)', $ids_to_remove);
                $db->safeQuery("DELETE FROM plugin_fts_meilisearch_item WHERE $statement_id", $statement_id->values());
                $statement_item_id = EasyStatement::open()->in('item_id IN (?*)', $ids_to_remove);
                $db->safeQuery("DELETE FROM plugin_fts_meilisearch_metadata WHERE $statement_item_id", $statement_item_id->values());
            }
        );
    }

    /**
     * @return IndexedItemFound[]
     */
    public function searchMatchingResultsByItemIDs(array $item_ids, array $cropped_content_by_id): array
    {
        $ids_statement = EasyStatement::open()->in('plugin_fts_meilisearch_item.id IN (?*)', $item_ids);
        /** @psalm-var array<int,string> $type_rows_by_id */
        $type_rows_by_id = $this->getDB()->safeQuery(
            "SELECT plugin_fts_meilisearch_item.id, plugin_fts_meilisearch_item.type
                FROM plugin_fts_meilisearch_item
                WHERE $ids_statement",
            $ids_statement->values(),
            \PDO::FETCH_KEY_PAIR
        );

        $statement_metadata_filter = EasyStatement::open()->in(
            'plugin_fts_meilisearch_metadata.item_id IN (?*)',
            $item_ids,
        );
        /** @psalm-var array<int,array{name: non-empty-string, value: string}[]> $metadata_rows_by_id */
        $metadata_rows_by_id = $this->getDB()->safeQuery(
            "SELECT plugin_fts_meilisearch_metadata.item_id, plugin_fts_meilisearch_metadata.name, plugin_fts_meilisearch_metadata.value
            FROM plugin_fts_meilisearch_metadata
            WHERE $statement_metadata_filter",
            $statement_metadata_filter->values(),
            \PDO::FETCH_GROUP | \PDO::FETCH_ASSOC
        );

        $results = [];
        foreach ($item_ids as $item_id) {
            $type = $type_rows_by_id[$item_id] ?? '';
            if ($type === '') {
                continue;
            }

            $metadata_key_value = [];
            foreach (($metadata_rows_by_id[$item_id] ?? []) as $metadata) {
                $metadata_key_value[$metadata['name']] = $metadata['value'];
            }

            if (count($metadata_key_value) === 0) {
                continue;
            }

            $results[] = new IndexedItemFound($type, $metadata_key_value, $cropped_content_by_id[$item_id] ?? null);
        }

        return $results;
    }
}
